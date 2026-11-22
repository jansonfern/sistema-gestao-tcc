# Guia de Deploy em Produção

## 1. Requisitos de Infraestrutura

- **Servidor Web:** Nginx ou Apache com suporte a HTTPS (certificado SSL/TLS válido)
- **Runtime:** PHP 8.2+ com extensões padrão do Laravel habilitadas
- **Banco de Dados:** MySQL 8.0+ ou PostgreSQL 14+
- **Cache e Filas (Opcional):** Redis para melhor desempenho em sessões e processamento assíncrono
- **Proxy Reverso (se aplicável):** Configure `TRUSTED_PROXIES` para que os logs de acesso e o rate limiting por IP registriem o endereço correto do cliente.

## 2. Variáveis de Ambiente (`.env`)

Configure o arquivo `.env` no servidor de produção desativando o modo de depuração e forçando cookies seguros:

```env
APP_NAME="Sistema Médico"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_medico
DB_USERNAME=app_user
DB_PASSWORD=senha_forte_aqui

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

MAIL_MAILER=smtp
MAIL_HOST=smtp.seu-provedor.com
MAIL_PORT=587
MAIL_USERNAME=usuario_smtp
MAIL_PASSWORD=senha_smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=suporte@seu-dominio.com
MAIL_FROM_NAME="Sistema Médico"

BACKUP_TIME=02:00
BACKUP_RETENTION_DAYS=14

# Apenas se houver proxy/túnel na frente da aplicação
# TRUSTED_PROXIES=*
```

## 3. Comandos de Publicação e Otimização

Após enviar o código para o servidor, execute:

```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan optimize
```

*(Nota: O comando `php artisan optimize` gera automaticamente o cache de configurações, rotas e views).*

## 4. Agendamento de Tarefas (Cron)

Adicione a entrada abaixo no `crontab` do servidor para habilitar o agendador do Laravel (necessário para a rotina de backup):

```bash
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Checklist Pós-Deploy

- [ ] Validar autenticação nos três perfis (Admin, Médico e Paciente).
- [ ] Testar fluxo de recuperação de senha via e-mail SMTP.
- [ ] Testar preenchimento automático de endereço via API ViaCEP.
- [ ] Executar `php artisan app:backup` manualmente para validar permissões de escrita em `storage/app/backups`.
- [ ] Verificar se o cabeçalho `Content-Security-Policy` está presente nas respostas.
