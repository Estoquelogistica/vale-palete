<?php
// Define um nome de sessão exclusivo para este sistema
session_name('VALE_PALETE_SESSID');
session_start();

// --- Logout por inatividade (Server-side) ---
$inactive_seconds = 300; // 5 minutos (5 * 60)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive_seconds) {
    // A última atividade foi há mais de 5 minutos, destrói a sessão
    session_unset();
    session_destroy();
    header("Location: login.php?reason=inactive"); // Redireciona para o login
    exit();
}
$_SESSION['last_activity'] = time(); // Atualiza o timestamp da última atividade
// --- Fim do controle de inatividade ---

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$nome_completo_usuario = $_SESSION['nome_completo'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html>
<head>
  <base target="_top">
  <meta charset="UTF-8">
  <title>Sistema de Vale Palete</title>
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    /* General Body & Layout */
    body {
        font-family: 'Inter', Arial, sans-serif; /* Usando fonte do novo design */
        background-color: #f8f9fb; /* Novo background */
        display: flex;
        min-height: 100vh;
        margin: 0;
    }

    /* Sidebar */
    .sidebar {
        width: 250px;
        background-color: #254c90; /* Nova cor da sidebar */
        color: white;
        padding: 0;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    .sidebar-header {
        padding: 20px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logo-container {
        background-color: white;
        border-radius: 8px; /* Bordas arredondadas */
        width: 130px; /* Largura ajustada para a nova moldura */
        padding: 0,5px; /* Espaçamento interno mínimo */
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 15px;
        overflow: hidden;
    }

    .logo-container img {
        max-width: 100%;
        height: auto;
    }

    .sidebar h2 {
        font-size: 1.2em;
        margin-bottom: 5px;
    }

    .sidebar h3 {
        font-size: 0.9em;
        opacity: 0.8;
    }

    /* Sidebar Menu */
    .sidebar-menu {
        flex-grow: 1;
        list-style: none;
        padding: 15px 0;
        margin: 0;
    }

    .sidebar-menu .nav-item {
        padding: 0 10px; /* Espaçamento para os itens */
    }

    .sidebar-menu .nav-link {
        display: block;
        padding: 12px 15px;
        color: white;
        text-decoration: none;
        transition: background-color 0.2s ease;
        font-size: 1em;
        border-radius: 0.5rem; /* Cantos arredondados */
        border: none; /* Remove bordas padrão do Bootstrap */
        margin-bottom: 5px; /* Espaço entre os itens */
    }

    .sidebar-menu .nav-link:hover {
        background-color: #1d3870; /* Nova cor de hover */
        color: white;
        border: none;
    }

    .sidebar-menu .nav-link.active {
        background-color: #1d3870; /* Nova cor de item ativo */
        color: white;
        font-weight: 500;
        border: none;
    }

    .sidebar-menu .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    /* Main Content Area */
    .main-content {
        flex-grow: 1;
        padding: 25px;
        background-color: #f8f9fb; /* Novo background */
        overflow-y: auto;
    }

    /* Novo Cabeçalho do Conteúdo Principal */
    .main-header {
        margin-bottom: 25px;
    }

    .main-header h1 {
        color: #254c90;
        font-weight: 700;
    }

    .content-section {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1); /* Nova sombra */
        margin-bottom: 20px;
    }

    /* Overrides de Componentes Bootstrap */
    .btn-primary {
        background-color: #254c90;
        border-color: #254c90;
    }

    .btn-primary:hover {
        background-color: #1d3870;
        border-color: #1d3870;
    }

    .alert {
        display: none;
    }

    .nav-tabs .nav-link.active {
        color: #254c90;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo-container">
        <img src="images/logo.svg" alt="Logo da Empresa">
      </div>
      <h2>Sistema Vale Palete</h2>
      <h3><?php echo htmlspecialchars($nome_completo_usuario); ?></h3>
    </div>
    <ul class="sidebar-menu nav nav-tabs flex-column" id="sidebarTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <a class="nav-link active" id="emitirVale-tab" data-bs-toggle="tab" href="#emitirVale" role="tab" aria-controls="emitirVale" aria-selected="true">
          <i class="fas fa-file-invoice"></i> Emitir Vale
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" id="baixarVale-tab" data-bs-toggle="tab" href="#baixarVale" role="tab" aria-controls="baixarVale" aria-selected="false">
          <i class="fas fa-download"></i> Baixar Vale
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" id="historico-tab" data-bs-toggle="tab" href="#historico" role="tab" aria-controls="historico" aria-selected="false">
          <i class="fas fa-history"></i> Histórico
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" id="cadastros-tab" data-bs-toggle="tab" href="#cadastros" role="tab" aria-controls="cadastros" aria-selected="false">
          <i class="fas fa-users"></i> Cadastros
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" id="configuracoes-tab" data-bs-toggle="tab" href="#configuracoes" role="tab" aria-controls="configuracoes" aria-selected="false">
          <i class="fas fa-cogs"></i> Configurações
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link" href="logout.php">
          <i class="fas fa-sign-out-alt"></i> Sair
        </a>
      </li>
    </ul>
  </div>

  <div class="main-content tab-content" id="nav-tabContent">
    <header class="main-header">
        <h1 id="pageTitle" class="h2"></h1>
    </header>
    <div id="messageArea" class="alert mt-3" role="alert"></div>

    <div class="tab-pane fade show active" id="emitirVale" role="tabpanel" aria-labelledby="emitirVale-tab">
      <div class="content-section">
        <h3>Emitir Novo Vale</h3>
        <form id="formEmitirVale">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="nVale" class="form-label">Nº Vale</label>
              <input type="text" class="form-control" id="nVale" readonly>
            </div>
            <div class="col-md-6 mb-3">
              <label for="dataEmissao" class="form-label">Data de Emissão</label>
              <input type="date" class="form-control" id="dataEmissao" name="data_emissao" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="lookupCpfCnpj" class="form-label">COD/CPF/CNPJ do Cliente</label>
              <input type="text" class="form-control" id="lookupCpfCnpj" placeholder="Digite para buscar..." required>
              <input type="hidden" id="hiddenCpfCnpj" name="cpf_cnpj_cliente">
            </div>
            <div class="col-md-6 mb-3">
              <label for="lookupNomeRazaoSocial" class="form-label">Nome / Razão Social</label>
              <input type="text" class="form-control" id="lookupNomeRazaoSocial" readonly>
            </div>
          </div>
          <div class="row">
            <div class="col-md-5 mb-3">
              <label for="tipoPalete" class="form-label">Tipo de Palete</label>
              <select class="form-select" id="tipoPalete" name="tipo_palete" required>
                <option value="">Selecione...</option>
              </select>
            </div>
            <div class="col-md-2 mb-3">
              <label for="quantidadeEmitida" class="form-label">Quantidade Emitida</label>
              <input type="number" class="form-control" id="quantidadeEmitida" name="quantidade_emitida" min="1" required>
            </div>
            <div class="col-md-2 mb-3">
              <label for="valorUnitario" class="form-label">Valor Unitário</label>
              <input type="text" class="form-control" id="valorUnitario" readonly style="background-color: #e9ecef;">
            </div>
            <div class="col-md-3 mb-3">
              <label for="valorTotal" class="form-label">Valor Total</label>
              <input type="text" class="form-control" id="valorTotal" readonly style="background-color: #e9ecef;">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="usuarioEmissor" class="form-label">Usuário Emissor</label>
              <input type="text" class="form-control" id="usuarioEmissor" name="usuario_emissor" value="<?php echo htmlspecialchars($nome_completo_usuario); ?>" readonly required style="background-color: #e9ecef;">
            </div>
            <div class="col-md-6 mb-3">
              <label for="descricaoEmissao" class="form-label">Descrição (Opcional)</label>
              <textarea class="form-control" id="descricaoEmissao" name="descricao" rows="1"></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Emitir Vale</button>
        </form>
      </div>
    </div>

    <div class="tab-pane fade" id="baixarVale" role="tabpanel" aria-labelledby="baixarVale-tab">
      <div class="content-section">
        <h3>Baixar Vale Existente</h3>
        <div class="mb-3">
          <input type="text" class="form-control" id="searchBaixarVale" placeholder="Buscar por Nº Vale, CPF/CNPJ ou Cliente...">
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Nº Vale</th>
                <th>Data Emissão</th>
                <th>Cliente</th>
                <th>Tipo Palete</th>
                <th>Qtd. Emitida</th>
                <th>Qtd. Baixada</th>
                <th>Saldo</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="valesParaBaixaTableBody">
              <!-- Conteúdo será carregado via JS -->
            </tbody>
          </table>
        </div>

        <div class="modal fade" id="baixarValeModal" tabindex="-1" aria-labelledby="baixarValeModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="baixarValeModalLabel">Registrar Baixa de Vale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="formBaixarVale">
                  <input type="hidden" id="modalNVale" name="n_vale">
                  <p><strong>Nº Vale:</strong> <span id="modalNValeDisplay"></span></p>
                  <p><strong>Saldo Atual:</strong> <span id="modalSaldo"></span></p>
                  <div class="mb-3">
                    <label for="quantidadeBaixar" class="form-label">Quantidade a Baixar</label>
                    <input type="number" class="form-control" id="quantidadeBaixar" name="quantidade_a_baixar" min="1" required>
                  </div>
                  <div class="mb-3">
                    <label for="usuarioBaixa" class="form-label">Usuário da Baixa</label>
                    <input type="text" class="form-control" id="usuarioBaixa" name="usuario_baixa" value="<?php echo htmlspecialchars($nome_completo_usuario); ?>" readonly required style="background-color: #e9ecef;">
                  </div>
                  <div class="mb-3">
                    <label for="descricaoBaixa" class="form-label">Descrição da Baixa (Opcional)</label>
                    <textarea class="form-control" id="descricaoBaixa" name="descricao_baixa" rows="2"></textarea>
                  </div>
                  <div id="modalMensagemErro" class="text-danger" style="display: none;"></div>
                  <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Confirmar Baixa</button>
                </form>
              </div>
            </div>
          </div>
        </div> <!-- Fim do Modal -->
      </div> <!-- Fim do content-section -->
    </div>

    <!-- =================================================================== -->
    <!-- INÍCIO DA SEÇÃO HISTÓRICO (VERSÃO RECONSTRUÍDA)                     -->
    <!-- =================================================================== -->
    <div class="tab-pane fade" id="historico" role="tabpanel" aria-labelledby="historico-tab">
      <div class="content-section">
        <h3>Histórico de Vales</h3>
        <div class="row g-3 align-items-end mb-3">
          <div class="col-md-3">
            <label for="searchHistorico" class="form-label">Buscar</label>
            <input type="text" class="form-control" id="searchHistorico" placeholder="Nº Vale, Cliente, etc...">
          </div>
          <div class="col-md-2">
            <label for="filterStartDate" class="form-label">Data Início</label>
            <input type="date" class="form-control" id="filterStartDate">
          </div>
          <div class="col-md-2">
            <label for="filterEndDate" class="form-label">Data Fim</label>
            <input type="date" class="form-control" id="filterEndDate">
          </div>
          <div class="col-md-2">
            <label for="filterStatus" class="form-label">Status</label>
            <select id="filterStatus" class="form-select">
              <option value="">Todos</option>
              <option value="Aberto">Aberto</option>
              <option value="Fechado">Fechado</option>
            </select>
          </div>
          <div class="col-md-3">
            <div class="d-grid gap-2 d-md-flex">
              <button class="btn btn-success flex-fill" id="exportCsvButton"><i class="fas fa-file-csv"></i> Exportar CSV</button>
              <button class="btn btn-danger flex-fill" id="exportPdfButton"><i class="fas fa-file-pdf"></i> Gerar PDF</button>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-bordered">
            <thead>
              <tr id="historicoTableHeader">
                <th>Nº Vale</th>
                <th>Data Emissão</th>
                <th>Cliente</th>
                <th>Tipo Palete</th>
                <th>Qtd. Emitida</th>
                <th>Qtd. Baixada</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody id="historicoTableBody">
              <!-- Conteúdo será carregado via JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- =================================================================== -->
    <!-- FIM DA SEÇÃO HISTÓRICO                                              -->
    <!-- =================================================================== -->

    <!-- Modal Detalhes do Vale -->
    <div class="modal fade" id="detalhesValeModal" tabindex="-1" aria-labelledby="detalhesValeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detalhesValeModalLabel">Detalhes do Vale</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="detalhesValeBody" class="table-responsive">
              <!-- Conteúdo será carregado via JS -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Cliente -->
    <div class="modal fade" id="editClienteModal" tabindex="-1" aria-labelledby="editClienteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editClienteModalLabel">Editar Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="formEditCliente">
              <div class="mb-3">
                <label for="editCpfCnpj" class="form-label">CPF/CNPJ</label>
                <input type="text" class="form-control" id="editCpfCnpj" name="cpf_cnpj" readonly style="background-color: #e9ecef;">
              </div>
              <div class="mb-3">
                <label for="editNomeRazaoSocial" class="form-label">Nome / Razão Social</label>
                <input type="text" class="form-control" id="editNomeRazaoSocial" name="nome_razao_social" required>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="cadastros" role="tabpanel" aria-labelledby="cadastros-tab">
      <div class="content-section">
        <h3>Gerenciar Cadastros</h3>
        <ul class="nav nav-tabs mb-3" id="cadastrosSubTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a class="nav-link active" id="clientes-tab" data-bs-toggle="tab" href="#clientes" role="tab" aria-controls="clientes" aria-selected="true">Clientes</a>
          </li>
          <li class="nav-item" role="presentation">
            <a class="nav-link" id="tiposPalete-tab" data-bs-toggle="tab" href="#tiposPalete" role="tab" aria-controls="tiposPalete" aria-selected="false">Tipos de Palete</a>
          </li>
        </ul>
        <div class="tab-content" id="cadastrosSubTabContent">
          <div class="tab-pane fade show active" id="clientes" role="tabpanel" aria-labelledby="clientes-tab">
            <h4>Cadastrar Novo Cliente</h4>
            <form id="addClienteForm" class="mb-4">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="newCpfCnpj" class="form-label">CPF/CNPJ</label>                  
                  <input type="text" class="form-control" id="newCpfCnpj" name="cpf_cnpj" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="newNomeRazaoSocial" class="form-label">Nome / Razão Social</label>
                  <input type="text" class="form-control" id="newNomeRazaoSocial" name="nome_razao_social" required>
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Adicionar Cliente</button>
            </form>
            <hr>
            <h4>Clientes Cadastrados</h4>
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>CPF/CNPJ</th>
                    <th>Nome / Razão Social</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody id="clientesTableBody">
                  <!-- Conteúdo será carregado via JS -->
                </tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="tiposPalete" role="tabpanel" aria-labelledby="tiposPalete-tab">
            <h4>Cadastrar Novo Tipo de Palete</h4>
            <form id="addTipoPaleteForm" class="mb-4">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="newTipoPalete" class="form-label">Tipo de Palete</label>
                  <input type="text" class="form-control" id="newTipoPalete" name="tipo_palete" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="newValorPalete" class="form-label">Valor Unitário (R$)</label>
                  <input type="number" step="0.01" class="form-control" id="newValorPalete" name="valor" required>
                </div>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-pallet"></i> Adicionar Tipo</button>
            </form>
            <h4>Tipos de Palete Cadastrados</h4>
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th>Tipo de Palete</th>
                    <th>Valor Unitário (R$)</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody id="tiposPaleteTableBody">
                  <!-- Conteúdo será carregado via JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="configuracoes" role="tabpanel" aria-labelledby="configuracoes-tab">
      <div class="content-section">
        <h3>Configurações do Sistema</h3>
        <form id="configForm">
          <div class="mb-3">
            <label for="nextValeNumberConfig" class="form-label">Próximo Número do Vale</label>
            <input type="number" class="form-control" id="nextValeNumberConfig" name="nextValeNumber" min="1" required>
            <div class="form-text">Define o próximo número a ser usado para um novo vale (ex: se você inserir 1, o próximo vale será VP00001).</div>
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Configurações</button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <script src="script.js"></script>
  <script>
    // --- Logout por inatividade (Client-side) ---
    (function() {
        const inactivityTime = 5 * 60 * 1000; // 5 minutos em milissegundos
        let logoutTimer;

        function resetTimer() {
            clearTimeout(logoutTimer);
            logoutTimer = setTimeout(logout, inactivityTime);
        }

        function logout() {
            // Redireciona para a página de logout para encerrar a sessão de forma segura
            window.location.href = 'logout.php';
        }

        // Eventos que indicam atividade do usuário e reiniciam o timer
        window.addEventListener('load', resetTimer);
        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keydown', resetTimer);
        document.addEventListener('click', resetTimer);
        document.addEventListener('scroll', resetTimer);
    })();
  </script>
  </body>
</html>
