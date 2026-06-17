# Comentário de documentação JamesBar
# Arquivo: README.md
# Função: Documentação do projeto JamesBar.

# 🍸 JamesBar System

Sistema de gerenciamento para casas noturnas e eventos desenvolvido para a disciplina de **Software Seguro / Cibersegurança**.

---

# 📋 Visão Geral

O JamesBar é um sistema web desenvolvido utilizando arquitetura MVC para gerenciamento de:

* Controle de entrada e saída de clientes
* Operação de caixas
* Controle de turnos e pausas
* Gerenciamento de promoters
* Listas VIP
* Listas de aniversário
* Painel administrativo
* Controle de acesso com MFA

O projeto foi desenvolvido seguindo requisitos de segurança definidos através de análise DFD/STRIDE e boas práticas de desenvolvimento seguro.

---

# 🏗 Arquitetura

```text
Frontend
    ↓
Controllers
    ↓
DAO
    ↓
Banco de Dados
```

### Frontend

Responsável pela interface do usuário.

```text
frontend/views
frontend/assets/css
frontend/assets/js
```

### Controllers

Responsáveis por receber requisições e aplicar regras de negócio.

```text
backend/controllers
```

### Models

Representam as entidades do sistema.

```text
backend/models
```

### DAO

Camada responsável pela persistência dos dados.

```text
backend/dao
```

### Security

Camada responsável pelos mecanismos de segurança.

```text
backend/security
```

---

# 🔐 Requisitos de Segurança Implementados

## 1. Autenticação obrigatória

**Arquivos:**

```text
frontend/assets/js/auth_check.js
backend/controllers/AuthController.php
```

Todas as páginas protegidas exigem sessão autenticada.

---

## 2. Controle de acesso por perfil

**Arquivos:**

```text
AdmController.php
CaixaController.php
PromoterController.php
```

O sistema diferencia usuários:

* Administrador
* Caixa

Impedindo acesso indevido a funcionalidades restritas.

---

## 3. MFA (Autenticação Multifator)

**Arquivos:**

```text
TotpService.php
AuthController.php
mfa.html
mfa.js
```

Administradores precisam validar um código TOTP gerado pelo Google Authenticator.

---

## 4. Criptografia híbrida para MFA

**Arquivo:**

```text
SecurityHelper.php
```

Utiliza:

* AES-256-GCM
* RSA

para proteção dos segredos MFA armazenados.

---

## 5. Hash seguro de senha

**Arquivos:**

```text
AuthController.php
UsuarioDAO.php
```

Utilização de:

```php
password_hash()
password_verify()
```

para armazenamento seguro das senhas.

---

## 6. Bloqueio após tentativas inválidas

**Arquivos:**

```text
AuthController.php
UsuarioDAO.php
```

Após cinco tentativas incorretas a conta é bloqueada temporariamente.

Campos utilizados:

```sql
tentativas_login
bloqueio_login_until
```

---

## 7. Troca obrigatória de senha

**Arquivos:**

```text
trocar_senha.html
trocar_senha.js
AuthController.php
```

Usuários criados pelo administrador devem alterar a senha no primeiro acesso.

---

## 8. Política de senha forte

**Arquivos:**

```text
SecurityHelper.php
trocar_senha.js
```

Validação de:

* Letras maiúsculas
* Letras minúsculas
* Números
* Caracteres especiais
* Comprimento mínimo

---

## 9. Proteção contra XSS

**Arquivo:**

```text
SecurityHelper.php
```

Utilização de:

```php
htmlspecialchars()
trim()
```

para sanitização das entradas.

---

## 10. Proteção contra SQL Injection

**Arquivos:**

```text
backend/dao/*
```

Todas as consultas utilizam:

```php
PDO::prepare()
bindParam()
```

seguindo boas práticas de codificação segura.

---

## 11. Logout automático por inatividade

**Arquivos:**

```text
caixa.js
AuthController.php
session.php
```

Usuários inativos são desconectados automaticamente.

---

## 12. Auditoria de turnos e pausas

**Arquivos:**

```text
TurnoDAO.php
jamesbar.sql
```

O sistema registra:

* Início do turno
* Pausas
* Retorno da pausa
* Encerramento do turno

---

## 13. Auditoria de ações

**Arquivos:**

```text
LogDAO.php
logs_sistema
```

Permite rastrear operações importantes realizadas pelos usuários.

---

## 14. Integridade automática dos clientes

**Arquivo:**

```text
jamesbar.sql
```

Trigger responsável por atualizar:

```sql
dentro_balada
total_entradas
```

automaticamente.

---

## 15. Remoção automática de listas expiradas

**Arquivo:**

```text
jamesbar.sql
```

Evento agendado responsável pela limpeza automática de listas antigas.

---

## 16. Variáveis de Ambiente

**Arquivos:**

```text
.env
Database.php
```

As credenciais do sistema ficam separadas do código-fonte.

Exemplo:

```env
DB_HOST=localhost
DB_NAME=db_core
DB_USER=root
```

---

## 17. Testes Automatizados

**Diretório:**

```text
backend/tests
```

Cobertura de:

* MFA
* Senhas
* Sessões
* Login
* Bloqueio de contas
* DAO
* SQL
* Frontend
* Regras de negócio
* Requisitos de segurança

---

## 18. GitHub Actions

**Arquivo:**

```text
.github/workflows/php-tests.yml
```

Executa os testes automaticamente em:

* Push
* Pull Request

---

# 🧪 Executando os Testes

```cmd
cd backend

composer install

vendor\bin\phpunit
```

---

# 🗄 Banco de Dados

Bancos utilizados:

```text
db_core
db_listas_vip
```

Principais tabelas:

```text
usuarios
clientes
movimentacoes
turnos_caixa
pausas_caixa
logs_sistema
promoters
listas_promoters
listas_aniversario
```

---

# 🔑 Funcionalidades Principais

### Administrador

* Login com MFA
* Gerenciamento de caixas
* Gerenciamento de promoters
* Controle de listas
* Acompanhamento operacional

### Caixa

* Consulta por CPF
* Cadastro de clientes
* Controle de entrada e saída
* Registro de pausas
* Encerramento de turno

---

# 🚀 Integração Contínua

Workflow configurado:

```text
.github/workflows/php-tests.yml
```

Valida automaticamente:

* Testes unitários
* Estrutura do projeto
* Regras de segurança
* Regras de negócio

---

# 👨‍💻 Autor

**André Komarcheuski Rosa & Ana Clara Saraiva Ribeiro Kelmer**

Projeto desenvolvido para a disciplina de Software Seguro / Cibersegurança.

---

# 📌 Conclusão

O JamesBar implementa mecanismos de autenticação, autorização, MFA, criptografia, proteção contra SQL Injection, proteção contra XSS, política de senhas fortes, controle de sessão, auditoria, testes automatizados e integração contínua, atendendo aos requisitos de segurança definidos para o projeto.
