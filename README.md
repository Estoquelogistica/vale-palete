# Sistema de Vale Palete

O Sistema de Vale Palete é uma aplicação web desenvolvida em PHP para gerenciar a emissão, baixa e o histórico de vales de paletes, facilitando o controle logístico e financeiro relacionado a esses ativos.

## ✨ Funcionalidades

*   **Autenticação Segura:** Sistema de login e logout com senhas criptografadas, utilizando uma tabela de usuários centralizada.
*   **Emissão de Vales:**
    *   Geração de novos vales com número sequencial automático.
    *   Busca inteligente de clientes por CPF/CNPJ.
    *   Cálculo automático do valor total com base na quantidade e no tipo de palete.
*   **Baixa de Vales:**
    *   Busca de vales abertos por número, cliente ou CPF/CNPJ.
    *   Registro de baixas parciais ou totais de paletes.
    *   Atualização automática de status (Aberto/Fechado).
*   **Histórico Completo:**
    *   Visualização de todos os vales emitidos.
    *   Filtros por período, status e busca geral.
    *   Opção para visualizar detalhes de cada vale, incluindo o histórico de baixas.
    *   Exportação de relatórios em formato CSV e PDF.
*   **Geração de PDF:**
    *   Impressão de vales em formato PDF com duas vias (Motorista e Empresa).
*   **Cadastros:**
    *   Gerenciamento de clientes (Adicionar, Editar, Listar).
    *   Gerenciamento de tipos de paletes e seus valores unitários.
*   **Configurações:**
    *   Ajuste do próximo número de vale a ser emitido.

## 🚀 Tecnologia Utilizada

*   **Backend:** PHP 8+
*   **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, Font Awesome
*   **Banco de Dados:** MySQL / MariaDB
*   **Dependências:**
    *   `mpdf/mpdf`: Para a geração de documentos PDF.

## 📋 Pré-requisitos

*   Servidor web local (XAMPP, WAMP, etc.) com suporte a PHP 8+ e MySQL.
*   Composer para gerenciamento de dependências.

## ⚙️ Instalação e Configuração

1.  **Clone o repositório** para a pasta `htdocs` do seu XAMPP (ou equivalente).
    ```bash
    # Exemplo:
    cd c:\xampp\htdocs\
    git clone <url-do-seu-repositorio> vale-palete
    ```

2.  **Instale as dependências** do PHP via Composer.
    ```bash
    cd vale-palete
    composer install
    ```

3.  **Configure o Banco de Dados:**

    O sistema utiliza dois bancos de dados:
    *   `intranet`: Para autenticação de usuários.
    *   `vale-palete`: Para os dados da aplicação.

    Execute os seguintes scripts SQL no seu gerenciador de banco de dados (phpMyAdmin, HeidiSQL, etc.):

    *   **Banco de dados de usuários (`intranet`):**
        ```sql
        CREATE DATABASE IF NOT EXISTS `intranet`;
        USE `intranet`;

        CREATE TABLE `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `password` varchar(255) NOT NULL,
          `department` varchar(100) DEFAULT NULL,
          `role` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- Crie um usuário de exemplo (senha: '123456')
        INSERT INTO `users` (username, password, role) VALUES ('admin', '$2y$10$e/CIg08J2S5aN3Wl.DWoA.i9x8jB0gBF.x7gQzYt25lV4V5f8fG.m', 'administrator');
        ```

    *   **Banco de dados da aplicação (`vale-palete`):**
        *Crie as tabelas `vales`, `clientes` e `valor_unitario` conforme a estrutura necessária para a aplicação.*

4.  **Configure a Conexão:**
    Certifique-se de que os arquivos de conexão com o banco de dados (ex: `config/database.php`) estão com as credenciais corretas (usuário `root` e senha em branco, por padrão no XAMPP).

5.  **Acesse o Sistema:**
    Abra o navegador e acesse `http://localhost/vale-palete/public/`. Você será redirecionado para a tela de login.

## 📁 Estrutura do Projeto

```
vale-palete/
├── api/                  # Scripts PHP para operações (CRUD, impressão)
├── config/               # Arquivos de configuração (ex: banco de dados)
├── public/               # Arquivos públicos (ponto de entrada da aplicação)
│   ├── images/           # Imagens (logo, background)
│   ├── index.php         # Interface principal do sistema
│   ├── login.php         # Tela de autenticação
│   ├── logout.php        # Script para encerrar a sessão
│   └── script.js         # Lógica do frontend
├── vendor/               # Dependências do Composer (mPDF)
├── composer.json         # Definição de dependências
└── README.md             # Esta documentação
```

## 📖 Como Usar

1.  **Login:** Acesse o sistema com um usuário e senha cadastrados na tabela `users` do banco `intranet`.
2.  **Cadastros:** Antes de emitir vales, vá para a aba "Cadastros" e adicione os tipos de paletes com seus respectivos valores e os clientes.
3.  **Emitir Vale:** Na aba "Emitir Vale", preencha os dados do cliente, tipo e quantidade de paletes para gerar um novo vale.
4.  **Baixar Vale:** Na aba "Baixar Vale", localize um vale em aberto e registre a devolução de paletes.
5.  **Histórico:** Consulte, filtre e exporte o histórico de todos os vales na aba "Histórico".

---

*Documentação gerada para o projeto Sistema de Vale Palete.*