# 🎣 projeto_defeso | ECOSIS - Sistema de Informação e Conscientização

# 🌊 Plataforma Educativa e Interativa sobre o Período de Defeso

Este projeto é uma plataforma interativa desenvolvida para **educar, informar e conscientizar** sobre o **Período de Defeso**. Focado em **educação ambiental**, **regras legais**, **direitos do pescador** e **promoção da cidadania informada**, com ênfase em usabilidade e análise de dados.

## ✨ Funcionalidades Principais

* **👤 Autenticação Completa:** Sistema de **Login e Cadastro** para acesso personalizado.
* **📰 Feed de Informações:** Uma página dinâmica com notícias, artigos e curiosidades sobre o defeso e a vida marinha.
* **⚖️ Simulação de Direitos:** Permite ao usuário logado realizar uma **Simulação** de elegibilidade ao auxílio-defeso.
* **📝 Feedback e Pesquisa:** Área para o usuário fornecer **Feedback** sobre o site e responder a **Pesquisas** sobre práticas de pesca.
* **📈 Auditoria de Dados:** Implementação de `TRIGGERS` para registrar a exclusão de dados importantes (Simulações, Pesquisas e Feedbacks), garantindo a integridade e rastreabilidade.

## 🧰 Tecnologias Utilizadas

| Categoria | Tecnologia |
| :--- | :--- |
| **Backend** | PHP (com XAMPP) |
| **Banco de Dados** | MySQL (phpMyAdmin) |
| **Frontend** | HTML5, CSS3, Bootstrap 5 |
| **Recursos** | Stored Procedures, Views e Triggers (MySQL) |

## 📁 Estrutura de Páginas

| Página | Descrição | Status no Projeto |
| :--- | :--- | :--- |
| `index.html` | Página inicial e de navegação principal. | Mantida |
| `regras.html` | Explicações das regras do defeso e quiz interativo. | Mantida |
| `informacoes.html` | Novo feed de notícias, artigos e curiosidades (Substitui 'denuncia.html'). | **NOVA** |
| `direitos.html` | Detalha os direitos e acesso à simulação de elegibilidade. | Mantida |
| `login.html` | Página de login e cadastro de novos usuários. | **NOVA** |
| `feedback.html` | Área logada para simulações e envio de feedback. | **NOVA** |

## 🛢️ Banco de Dados (site_defeso)

A estrutura do banco de dados foi completamente redesenhada para suportar a autenticação de usuários e o registro das interações.

### Tabelas Principais (5)

| Tabela | Chave Estrangeira | Descrição |
| :--- | :--- | :--- |
| **usuarios** | --- | Gerencia o **Login/Cadastro**. Armazena `id_usuario`, `nome`, `email` e `senha_hash`. |
| **simulacoes** | `id_usuario` | Registra os resultados de cada simulação feita pelo usuário (`resultado` e `data_simulacao`). |
| **respostas_pesquisa** | `id_usuario` | Registra as respostas de pesquisa do usuário sobre embarcação, espécies, etc. |
| **feedbacks** | `id_usuario` | Armazena as mensagens de feedback enviadas pelos usuários. |
| **log_auditoria** | --- | Tabela de log para registrar as exclusões de dados importantes por meio de `TRIGGERS`. |

### Estruturas Avançadas (3)

1.  **VIEW (`vw_simulacoes_classificadas`):** Classifica automaticamente o resultado da simulação em 'APROVADO (Baixo Risco)' ou 'REVISÃO URGENTE (Alto Risco)' usando uma declaração `CASE`.
2.  **STORED PROCEDURE (`sp_relatorio_total_simulacoes`):** Calcula o número total de simulações registradas e a data do último registro.
3.  **TRIGGERS (`trg_delete_simulacoes`, `trg_delete_pesquisa`, `trg_delete_feedback`):** Acionados *antes* de deletar uma linha nas tabelas correspondentes, registrando a ação na tabela `log_auditoria`.

## ⚙️ Como Executar Localmente (XAMPP)

1.  Baixe e instale o [XAMPP](https://www.apachefriends.org/index.html).
2.  Coloque a pasta do projeto (`projeto_defeso` ou similar) dentro de: `C:\xampp\htdocs\`
3.  Inicie os serviços do **Apache** e **MySQL** no painel de controle do XAMPP.
4.  Crie o banco de dados `site_defeso` (usando o código SQL fornecido) via phpMyAdmin.
5.  Acesse o projeto no navegador: `http://localhost/nome-da-sua-pasta/`

---

Desenvolvido por: **Jonatas Gabriel** (Desenvolvedor)  
Projeto Acadêmico | Universidade CEUMA | Ano: 2025