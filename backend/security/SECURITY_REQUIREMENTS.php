<?php

/**
 * DOCUMENTAÇÃO DOS REQUISITOS DE SEGURANÇA DO JAMESBAR
 *
 * Este arquivo centraliza os principais requisitos de segurança implementados
 * no projeto para facilitar a apresentação e a avaliação acadêmica.
 *
 * REQUISITO DE SEGURANÇA: Autenticação obrigatória
 * O sistema exige login antes de permitir acesso aos dashboards de ADM e Caixa.
 * Implementado em AuthController.php, auth_check.js e controllers protegidos.
 *
 * REQUISITO DE SEGURANÇA: Controle de sessão
 * O backend valida se existe sessão ativa antes de retornar dados protegidos.
 * Implementado com $_SESSION e função jb_require_login().
 *
 * REQUISITO DE SEGURANÇA: Controle de autorização por perfil
 * O sistema separa permissões entre adm e caixa, impedindo acesso indevido.
 * Implementado com jb_require_login('adm') e jb_require_login('caixa').
 *
 * REQUISITO DE SEGURANÇA: MFA para administrador
 * Usuários do tipo adm precisam validar código do Google Authenticator.
 * Implementado em AuthController.php e TotpService.php.
 *
 * REQUISITO DE SEGURANÇA: Criptografia híbrida do segredo MFA
 * O segredo MFA é protegido com AES-256-GCM e a chave AES é protegida com RSA.
 * Implementado em SecurityHelper.php e salvo em mfa_secret/mfa_secret_key.
 *
 * REQUISITO DE SEGURANÇA: Prepared Statements contra SQL Injection
 * Os DAOs usam PDO prepare(), bindParam() e execute() para consultas SQL.
 * Implementado na pasta backend/dao.
 *
 * REQUISITO DE SEGURANÇA: Sanitização contra XSS
 * Entradas textuais são filtradas com htmlspecialchars(), trim() e validações.
 * Implementado em SecurityHelper.php e usado nos controllers.
 *
 * REQUISITO DE SEGURANÇA: Validação por Regex
 * O sistema valida CPF, e-mail, telefone, senha forte, MFA e datas.
 * Implementado em SecurityHelper.php.
 *
 * REQUISITO DE SEGURANÇA: Bloqueio temporário após tentativas inválidas
 * Após 5 tentativas de login incorretas, o usuário é bloqueado por 10 minutos.
 * Implementado em AuthController.php e UsuarioDAO.php.
 *
 * REQUISITO DE SEGURANÇA: Troca obrigatória de senha no primeiro acesso
 * Caixas cadastrados pelo ADM precisam trocar a senha antes de usar o sistema.
 * Implementado em AuthController.php e tela trocar_senha.html.
 *
 * REQUISITO DE SEGURANÇA: Logout automático por inatividade
 * Caixa inativo é desconectado e o turno entra como pausa.
 * Implementado no frontend e em AuthController.php/CaixaController.php.
 *
 * REQUISITO DE SEGURANÇA: Registro de pausas e turnos
 * O sistema registra início/fim de turno, pausas e retorno do caixa.
 * Implementado em TurnoDAO.php e tabelas turnos_caixa/pausas_caixa.
 *
 * REQUISITO DE SEGURANÇA: Integridade de listas de promoters
 * Listas enviadas não são editadas/apagadas pelo usuário e possuem limite de nomes.
 * Implementado em PromoterDAO.php e PromoterController.php.
 *
 * REQUISITO DE SEGURANÇA: Exclusão automática de listas expiradas
 * Listas antigas são removidas após a data do evento.
 * Implementado no evento SQL apagar_listas_expiradas e em limparListasAntigas().
 *
 * REQUISITO DE SEGURANÇA: Testes automatizados e CI/CD
 * O projeto possui PHPUnit e GitHub Actions para validar segurança e qualidade.
 * Implementado em backend/tests e .github/workflows/php-tests.yml.
 */
final class SECURITY_REQUIREMENTS {}
