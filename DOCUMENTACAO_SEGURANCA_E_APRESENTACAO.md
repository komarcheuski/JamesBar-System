# JamesBar - Revisão Final de Segurança e Apresentação

## 10+ requisitos de segurança documentados no código

1. **Autenticação obrigatória nas páginas**  
   Arquivo: `frontend/assets/js/auth_check.js` e `backend/controllers/AuthController.php`  
   Verifica sessão antes de permitir uso das páginas protegidas.

2. **Controle de acesso por perfil ADM**  
   Arquivos: `AdmController.php` e `PromoterController.php`  
   Usa `jb_require_login('adm')` para impedir caixa ou usuário não logado de acessar funções administrativas.

3. **Controle de acesso por perfil CAIXA**  
   Arquivo: `CaixaController.php`  
   Usa `jb_require_login('caixa')` para proteger rotas de operação do caixa.

4. **MFA com Google Authenticator**  
   Arquivos: `TotpService.php`, `AuthController.php`, `mfa.js`, `mfa.html`  
   Usuário ADM precisa confirmar código TOTP antes de acessar o dashboard.

5. **Criptografia híbrida do segredo MFA**  
   Arquivo: `SecurityHelper.php`  
   Usa AES-256-GCM para criptografar o segredo MFA e RSA para proteger a chave AES.

6. **Bloqueio temporário após 5 tentativas inválidas**  
   Arquivos: `AuthController.php`, `UsuarioDAO.php`, `jamesbar.sql`  
   Controla `tentativas_login` e `bloqueio_login_until`.

7. **Troca obrigatória de senha no primeiro acesso**  
   Arquivos: `AuthController.php`, `trocar_senha.js`, `trocar_senha.html`  
   Redireciona usuário com `primeiro_acesso = TRUE` para criar senha forte.

8. **Validação de senha forte**  
   Arquivos: `SecurityHelper.php`, `trocar_senha.js`  
   Exige maiúscula, minúscula, número, símbolo e tamanho mínimo.

9. **Sanitização contra XSS**  
   Arquivo: `SecurityHelper.php`  
   Usa `htmlspecialchars`, `trim` e limpeza de caracteres perigosos.

10. **Prepared Statements contra SQL Injection**  
    Arquivos: `backend/dao/*.php`  
    Usa PDO com `prepare`, `bindParam` e parâmetros nomeados.

11. **Logout automático por inatividade**  
    Arquivos: `caixa.js`, `AuthController.php`, `TurnoDAO.php`  
    Caixa inativo é desconectado e a pausa é registrada.

12. **Auditoria operacional de turnos e pausas**  
    Arquivos: `TurnoDAO.php`, `jamesbar.sql`  
    Guarda abertura, pausa, retorno e fechamento do turno.

13. **Integridade automática do status do cliente**  
    Arquivo: `jamesbar.sql`  
    Trigger atualiza `dentro_balada` e `total_entradas`.

14. **Remoção automática de listas antigas**  
    Arquivos: `PromoterDAO.php`, `jamesbar.sql`  
    Remove listas vencidas após a data do evento.

15. **Testes automatizados e GitHub Actions**  
    Arquivos: `backend/tests/*` e `.github/workflows/php-tests.yml`  
    Valida segurança, estrutura MVC, SQL e frontend.

## Como apresentar passo a passo

1. **Abrir o projeto e explicar arquitetura**
   - `frontend/views`: telas HTML.
   - `frontend/assets/js`: regras de interação e validação visual.
   - `backend/controllers`: recebem requisições.
   - `backend/dao`: acesso ao banco com Prepared Statements.
   - `backend/models`: entidades MVC.
   - `backend/security`: sanitização, MFA e criptografia.

2. **Mostrar o banco**
   - Executar `backend/database/jamesbar.sql`.
   - Mostrar `usuarios` com `mfa_secret` e `mfa_secret_key`.
   - Mostrar `tentativas_login` e `bloqueio_login_until`.

3. **Demonstrar login ADM**
   - Login com `admin@jamesbar.com`.
   - Mostrar redirecionamento para MFA.
   - Configurar Google Authenticator.
   - Entrar no dashboard ADM.

4. **Demonstrar segurança de acesso**
   - Tentar abrir dashboard ADM sem login.
   - Mostrar que o `auth_check.js` chama o backend.
   - Mostrar que os Controllers também exigem perfil.

5. **Demonstrar bloqueio por tentativas**
   - Errar senha 5 vezes.
   - Mostrar mensagem de bloqueio por 10 minutos.

6. **Demonstrar troca obrigatória de senha**
   - Login de caixa com primeiro acesso.
   - Mostrar tela de troca de senha e força de senha.

7. **Demonstrar caixa**
   - Entrar como caixa.
   - Buscar CPF.
   - Cadastrar cliente.
   - Liberar entrada.
   - Pausar e encerrar turno.

8. **Demonstrar ADM**
   - Cadastrar/editar/excluir promoter.
   - Criar lista de promoter.
   - Criar lista de aniversário.
   - Ver status de envio.

9. **Mostrar os requisitos no código**
   - Procurar por `REQUISITO DE SEGURANÇA:` no projeto.
   - Explicar cada bloco documentado.

10. **Rodar testes**
    ```cmd
    cd C:\xampp\htdocs\JAMESBAR\backend
    composer install
    composer test
    ```

11. **Mostrar GitHub Actions**
    - Abrir `.github/workflows/php-tests.yml`.
    - Explicar que os testes rodam no push e pull request.

## Observações da revisão

- Corrigido o `auth_check.js` para usar `verificar_mfa_sessao`, agora implementado no `AuthController`.
- Corrigida referência quebrada `loading.gif` para `Drink.gif`.
- PHP passou em verificação de sintaxe (`php -l`) nos arquivos PHP.
- Como o Composer não está disponível no ambiente da revisão, os testes PHPUnit foram preparados para rodar localmente/GitHub Actions.
