# Quick Chef

Aplicação web de uso interno para a cantina de uma empresa, criada para promover uma alimentação mais saudável, aumentar a produtividade e reduzir o desperdício alimentar através da consulta antecipada das ementas semanais. Os colaboradores podem consultar refeições com informação nutricional detalhada, marcar refeições e dar feedback; os administradores gerem ementas, utilizadores e avaliações.

## Funcionalidades

**Utilizador (colaborador)**
- Registo e login com password encriptada
- Perfil pessoal com preferências alimentares, tipo de dieta e meta calórica
- Dashboard com navegação por dia da semana (setas), mostrando as refeições e informação nutricional (kcal, proteínas, hidratos de carbono, lípidos, fibras)
- Etiquetas de restrição alimentar (Vegan, Vegetariano, Sem Glúten, Sem Lactose), com filtragem automática das refeições de acordo com o perfil
- Marcação de refeições, ligada ao dia efetivamente visualizado no dashboard
- Avaliação de refeições através de três emojis (positivo, neutro, negativo)

**Administração**
- CRUD de ementas e refeições
- Gestão de utilizadores
- Consulta de marcações
- Painel de feedback com pontuação média de popularidade por prato

## Tecnologias

- **Frontend**: HTML + CSS (uma folha de estilo dedicada por página)
- **Backend**: PHP (sessões, autenticação, CRUD, API)
- **Base de dados**: MySQL (`BD_QuickChef.sql`)
- **Ambiente local**: Wampserver (Apache + PHP) e MySQL Workbench

## Estrutura do projeto

```
quickchefcodigo/
├── index.php                 # Página inicial
├── dashboard.php              # Dashboard principal do utilizador
├── perfil.php                 # Perfil e preferências
├── marcar.php                  # Marcação de refeições
├── refeicao.php
├── BD_QuickChef.sql             # Esquema da base de dados
├── admin/
│   ├── index.php
│   ├── ementas.php
│   ├── ementa_semanal.php
│   ├── marcacoes.php
│   ├── utilizadores.php
│   └── feedback.php
├── api/
│   └── avaliar.php              # Registo de avaliações (emojis)
├── includes/
│   ├── ligacao.php               # Ligação à base de dados
│   ├── navbar.php
│   ├── auth.php
│   └── auth_admin.php
├── login/
│   ├── login.php
│   ├── registar.php
│   ├── autenticar.php
│   └── logout.php
├── css/                          # Uma folha de estilo por página
└── imagens/
```

## Como correr o projeto

1. Instalar o [Wampserver](https://www.wampserver.com/) (ou outra stack Apache + PHP + MySQL).
2. Colocar a pasta `quickchefcodigo/` dentro de `www/` do Wampserver.
3. Criar a base de dados `quickchef` e importar `BD_QuickChef.sql` (via phpMyAdmin ou MySQL Workbench).
4. Confirmar as credenciais em `includes/ligacao.php` (por omissão: `localhost`, utilizador `root`, sem password).
5. Aceder a `http://localhost/quickchefcodigo/`.

## Segurança

- Passwords encriptadas com `password_hash()` / `password_verify()` (bcrypt)
- Sessões PHP com expiração por inatividade
- Acesso ao painel de administração protegido por `auth_admin.php`

## Bugs corrigidos durante os testes finais

O relatório documenta seis correções relevantes:

1. **Login incompatível** — passwords estavam a ser cifradas com `MD5()`/`SHA1()` no SQL, incompatíveis com `password_verify()` do PHP; solução centralizada em `password_hash()`/`password_verify()`.
2. **Ícones/links quebrados na navbar** — caminhos relativos falhavam em subpastas como `/admin/`; resolvido com uma constante global `PROJETO` em `navbar.php`.
3. **CSS em falta no painel admin** — caminhos absolutos incorretos e folhas de estilo incompletas; corrigido com caminhos relativos e CSS dedicado por página.
4. **Fins de semana a redirecionar para sexta-feira** — limite da função de dia da semana estava fixado em `min(..., 5)`; alargado para 7 dias, com aviso de "cantina encerrada" ao fim de semana.
5. **Filtros de restrição alimentar não aplicados** — a query do dashboard não cruzava as preferências do perfil com as colunas de restrição das refeições; corrigido com cláusulas `WHERE` dinâmicas.
6. **Botão "Marcar refeição" ignorava o dia visualizado** — faltava passar o dia por query string; corrigido com `marcar.php?dia=<?= $dia_sel ?>`.

## Equipa

Equipa 1: Maria e André (planeamento, design, PHP/CSS), Miguel (infraestrutura/VirtualBox), Joaquim Santos (base de dados MySQL, debugging e testes).

## Trabalho futuro

Expandir o catálogo de pratos e ingredientes, reforçar auditoria de segurança e testes de carga, e explorar recomendações personalizadas com IA.

## Documentação adicional

Relatório completo do projeto em `QC Relatório.docx` (contexto, requisitos, arquitetura, wireframes, algoritmos e conclusões).
