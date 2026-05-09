# Site Multiempresas — Criado do Zero

Este projeto foi refeito do zero, em uma estrutura organizada por arquivos.

## O que tem

- Login
- Criar conta
- Admin aprova usuários
- Admin define perfil: admin ou usuário
- Admin libera empresas por usuário
- Banco de dados MySQL multiempresas
- Empresas iniciais:
  - PARISVIU
  - IDDV
  - LIBERTY
  - LEPARQUE
- Admin importa planilha por empresa
- Importa XLSX ou CSV
- Reconhece colunas do relatório da Meta
- Atualiza:
  - campanhas
  - conjuntos de anúncios
  - anúncios/criativos
  - métricas
  - histórico de importações
- Dashboard por empresa
- Dashboard por campanha
- Histórico de importações

## Arquivos principais

- index.php
- config.php
- database.sql
- app/
- assets/style.css

## Instalação na Hostinger

1. Crie um banco MySQL no hPanel.
2. Abra o phpMyAdmin.
3. Importe o arquivo `database.sql`.
4. Edite o arquivo `config.php`:
   - db_host
   - db_name
   - db_user
   - db_pass
5. Suba todos os arquivos para a raiz do site.
6. Acesse o site.

## Login admin inicial

E-mail: kevinnikolas417@gmail.com
Senha: 123456

O sistema cria esse admin automaticamente no primeiro acesso, caso ele ainda não exista no banco.

## Atualização dos dados

1. Entre como admin.
2. Clique em `Importar relatório`.
3. Escolha a empresa.
4. Escolha a conta de anúncio.
5. Envie a planilha XLSX ou CSV exportada da Meta.
6. O banco será atualizado automaticamente.
