# Sistema de Gestão Médica

Aplicação web desenvolvida em Laravel 11 para gerenciamento de clínicas médicas, contemplando o cadastro de pacientes e médicos, controle de escalas de atendimento, agendamento de consultas e emissão de comprovantes em PDF.

## Funcionalidades Principais

- **Controle de Acesso (RBAC):** Três perfis distintos (`admin`, `médico` e `paciente`) com rotas protegidas via middleware customizado (`CheckRole`).
- **Gestão de Pacientes e Médicos:** Operações completas de CRUD com validação de unicidade para CPF e CRM, além de preenchimento automático de endereço via integração com a API ViaCEP.
- **Escalas e Agendamentos:** Definição de disponibilidade por médico, data e horário, com controle de conflitos para agendamento de consultas.
- **Emissão de Documentos:** Geração de comprovantes em PDF utilizando `barryvdh/laravel-dompdf`.
- **Notificações:** Envio de e-mails transacionais para agendamento, confirmação, cancelamento de consultas e eventos de conta.
- **Segurança e Auditoria:** Headers de segurança HTTP (CSP com nonce, X-Frame-Options, X-Content-Type-Options), rate limiting configurável por ambiente e logs de auditoria para tentativas de login (sucesso/falha, IP e user-agent).

## Tecnologias Utilizadas

- **Back-end:** PHP 8.2+, Laravel 11 (Eloquent ORM, Migrations, Seeders, Middlewares)
- **Front-end:** Blade Templates, Bootstrap 5.3, Font Awesome 6.4, Vite
- **Banco de Dados:** MySQL / MariaDB (produção/desenvolvimento) e SQLite em memória (testes automatizados)
- **Testes:** PHPUnit 11 (cobertura de autenticação, rate limiting e notificações)

---

## Requisitos do Ambiente

- PHP >= 8.2 (com extensões padrão do Laravel habilitadas: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `bcmath`)
- Composer
- Node.js e NPM
- MySQL ou MariaDB

---

## Instalação e Execução

1. **Clone o repositório e acesse a pasta do projeto:**
   ```bash
   git clone <url-do-repositorio>
   cd sistema-tcc
   ```

2. **Instale as dependências do PHP e do Front-end:**
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Configure as variáveis de ambiente:**
   Copie o arquivo de exemplo e gere a chave da aplicação:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure o banco de dados no arquivo `.env`:**
   Crie um banco chamado `sistema_medico` no seu servidor MySQL/MariaDB e ajuste as credenciais no `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sistema_medico
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Execute as migrações e popule o banco com dados iniciais:**
   ```bash
   php artisan migrate --seed
   ```

6. **Inicie o servidor local:**
   ```bash
   php artisan serve
   ```
   A aplicação estará acessível em `http://localhost:8000`.

> **Nota para ambientes Linux:** Caso ocorra erro de permissão de escrita, ajuste os diretórios de cache e logs com `chmod -R 775 storage bootstrap/cache`.

---

## Credenciais de Teste

Ao executar `php artisan migrate --seed`, os seguintes usuários são criados automaticamente para avaliação dos perfis (senha de todos: `password`):

| Perfil | E-mail |
| :--- | :--- |
| **Administrador** | `admin@clinica.com` |
| **Médico** | `joao.silva@clinica.com` |
| **Médico** | `maria.santos@clinica.com` |
| **Paciente** | `carlos@email.com` |
| **Paciente** | `ana@email.com` |

---

## Testes Automatizados

O projeto utiliza SQLite em memória (configurado via `phpunit.xml`) para rodar a suíte de testes sem interferir no banco principal:

```bash
php artisan test
```

## Comandos Úteis de Manutenção

```bash
# Limpar todos os caches da aplicação (rotas, configurações, views)
php artisan optimize:clear

# Recriar o banco de dados do zero com os dados de teste
php artisan migrate:fresh --seed
```

---

## Documentação Complementar

- [Política de Backup e Recuperação](docs/BACKUP.md)
- [Guia de Deploy em Produção](docs/DEPLOY.md)
- [Diretrizes de Adequação à LGPD](docs/LGPD.md)
