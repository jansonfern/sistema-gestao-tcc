# Política de Backup e Recuperação

## Comando Disponível

A aplicação conta com um comando Artisan customizado para rotina de backup e limpeza de retenção:

```bash
php artisan app:backup
```

### Comportamento por Driver
- **SQLite:** Realiza a cópia direta do arquivo `database/database.sqlite` para o diretório `storage/app/backups`.
- **MySQL / PostgreSQL:** Exibe instrução para uso das ferramentas nativas do servidor (`mysqldump` ou `pg_dump`) e gerencia a exclusão de arquivos antigos conforme a política de retenção.
- **Limpeza Automática:** Remove arquivos de backup que excederem o prazo definido na variável `BACKUP_RETENTION_DAYS`.

## Agendamento Automático

A rotina está registrada no *Task Scheduler* do Laravel (`routes/console.php`) para execução diária no horário configurado em `BACKUP_TIME`.

## Boas Práticas Recomendadas (Produção)

- **Frequência:** Backup completo diário em horário de baixo tráfego.
- **Retenção:** Manter histórico local entre 14 e 30 dias.
- **Redundância Externa:** Enviar cópias para armazenamento externo isolado (ex.: Amazon S3, Google Cloud Storage ou bucket privado).
- **Auditoria:** Realizar testes periódicos (mensais) de restauração em ambiente de homologação.

## Procedimento de Restauração (Ambiente SQLite)

1. Interrompa o servidor web ou coloque a aplicação em modo de manutenção (`php artisan down`).
2. Substitua o arquivo `database/database.sqlite` pela cópia desejada de `storage/app/backups/`.
3. Reative a aplicação (`php artisan up`) e valide o acesso, listagem de pacientes e agendamentos.
