# Adequação à LGPD (Diretrizes Base)

> **Nota:** Este documento estabelece as diretrizes técnicas iniciais de privacidade e proteção de dados aplicadas na arquitetura do sistema, conforme a Lei Geral de Proteção de Dados (Lei nº 13.709/2018). Para operação comercial, recomenda-se revisão jurídica especializada.

## 1. Mapeamento de Dados Tratados

- **Dados Cadastrais:** Nome completo, e-mail, telefone, CPF e data de nascimento.
- **Dados Operacionais e Sensíveis:** Histórico de agendamentos, especialidades médicas e informações de atendimento (dados referentes à saúde, protegidos pelo Art. 11 da LGPD).
- **Dados Técnicos e Auditoria:** Endereço IP, *User-Agent* e registros de data/hora de tentativas de autenticação.

## 2. Finalidades do Tratamento

- Viabilizar o agendamento de consultas e a gestão operacional da clínica.
- Garantir a segurança da informação, rastreabilidade de ações e prevenção contra fraudes.
- Enviar comunicações transacionais essenciais (como redefinição de senha e confirmações).

## 3. Bases Legais Aplicáveis

- **Execução de Contrato ou Procedimentos Preliminares:** Para o agendamento e prestação do atendimento médico.
- **Tutela da Saúde:** Aplicável aos dados sensíveis tratados no contexto de serviços de saúde.
- **Legítimo Interesse:** Para monitoramento de segurança, logs de auditoria e proteção contra acessos não autorizados.
- **Cumprimento de Obrigação Legal ou Regulatória:** Guarda de registros exigidos por conselhos de classe ou normas sanitárias.

## 4. Direitos do Titular dos Dados

O sistema deve prover meios para atender às solicitações previstas no Art. 18 da LGPD:
- Confirmação da existência de tratamento e acesso aos dados cadastrados.
- Correção de dados incompletos, inexatos ou desatualizados.
- Eliminação ou anonimização de dados pessoais quando encerrada a finalidade, ressalvadas as hipóteses de guarda legal.

## 5. Medidas Técnicas de Segurança Implementadas

- **Controle de Acesso (RBAC):** Segregação estrita de permissões entre administradores, médicos e pacientes.
- **Criptografia:** Armazenamento de senhas utilizando hash seguro (Bcrypt) e tráfego protegido via HTTPS/TLS.
- **Proteção contra Abuso:** *Rate limiting* nas rotas críticas e headers de segurança HTTP (CSP, X-Frame-Options).
- **Auditoria e Continuidade:** Registro de logs de autenticação e rotina automatizada de backup com política de retenção.

## 6. Canal de Atendimento (Encarregado / DPO)

Para exercício dos direitos do titular ou dúvidas sobre privacidade em ambiente de produção, deve ser disponibilizado um canal oficial (ex.: `privacidade@seu-dominio.com`).
