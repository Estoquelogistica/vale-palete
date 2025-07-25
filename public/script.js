let valeDetailsData = {}; // Depósito global para os detalhes dos vales

document.addEventListener('DOMContentLoaded', () => {
    // --- INICIALIZAÇÃO ---
    initializePage();
    initializeEventListeners();
});

function initializePage() {
    // Carrega dados APENAS para a primeira aba visível (Emitir Vale)
    loadDataForEmitirTab();
    updatePageTitle(); // Define o título inicial da página
}

function initializeEventListeners() {
    // Função auxiliar para adicionar listeners de forma segura, evitando erros
    const addSafeListener = (id, event, handler) => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener(event, handler);
        } else {
            // Este log ajuda a encontrar IDs ausentes no HTML sem quebrar a aplicação
            console.warn(`Elemento com ID '${id}' não encontrado. Listener não adicionado.`);
        }
    };

    // Listener central para atualizar o título da página ao trocar de aba
    addSafeListener('sidebarTabs', 'shown.bs.tab', updatePageTitle);

    addSafeListener('formEmitirVale', 'submit', handleEmitirVale);
    addSafeListener('addClienteForm', 'submit', handleAddCliente);
    addSafeListener('formBaixarVale', 'submit', handleBaixarVale);
    addSafeListener('lookupCpfCnpj', 'input', debounce(handleClientLookup, 300));
    addSafeListener('searchBaixarVale', 'input', debounce(() => loadValesParaBaixa(), 300));
    addSafeListener('searchHistorico', 'input', debounce(() => loadHistorico(), 300));
    addSafeListener('filterStartDate', 'change', loadHistorico);
    addSafeListener('filterEndDate', 'change', loadHistorico);
    addSafeListener('filterStatus', 'change', loadHistorico);
    addSafeListener('addTipoPaleteForm', 'submit', handleAddTipoPalete);
    addSafeListener('tipoPalete', 'change', updateValores);
    addSafeListener('quantidadeEmitida', 'input', updateValores);
    addSafeListener('configForm', 'submit', handleSaveConfiguracoes);
    addSafeListener('formEditCliente', 'submit', handleUpdateCliente);

    // --- CARREGAMENTO DE DADOS SOB DEMANDA (AO CLICAR NA ABA) ---
    addSafeListener('baixarVale-tab', 'shown.bs.tab', loadValesParaBaixa);
    addSafeListener('historico-tab', 'shown.bs.tab', loadHistorico);
    addSafeListener('configuracoes-tab', 'shown.bs.tab', loadConfiguracoes);

    // A aba de cadastros tem sub-abas, então precisa de uma lógica especial.
    const cadastrosTab = document.getElementById('cadastros-tab');
    if (cadastrosTab) {
        cadastrosTab.addEventListener('shown.bs.tab', () => {
            loadClientesTable();
            addSafeListener('clientes-tab', 'shown.bs.tab', loadClientesTable);
            addSafeListener('tiposPalete-tab', 'shown.bs.tab', loadTiposPaleteTable);
        }, { once: true });
    }
}

// --- FUNÇÕES DE CARREGAMENTO DE DADOS (LOADERS) ---

function loadDataForEmitirTab() {
    loadTiposPaleteParaSelect();
    setInitialEmitirFormValues();
}

async function loadHistorico() {
    const tbody = document.getElementById('historicoTableBody');
    if (!tbody) {
        console.error("Elemento 'historicoTableBody' não encontrado. A aba de histórico está correta no HTML?");
        return;
    }

    // 1. Coleta os valores dos filtros
    const searchValue = document.getElementById('searchHistorico').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const status = document.getElementById('filterStatus').value;

    // 2. Monta a URL com os parâmetros de forma segura
    const params = new URLSearchParams({
        search: searchValue,
        start_date: startDate,
        end_date: endDate,
        status: status
    });
    const url = `/vale-palete/api/vales/historico.php?${params.toString()}`;

    // Limpa o depósito de detalhes a cada nova busca
    valeDetailsData = {};
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Carregando...</td></tr>';

    try {
        // 3. Faz a requisição para o backend
        const response = await fetch(url);
        if (!response.ok) throw new Error(`Erro na rede: ${response.status} ${response.statusText}`);

        const vales = await response.json();

        // 4. Limpa a tabela e renderiza os novos dados
        tbody.innerHTML = '';
        if (vales.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center fst-italic p-3">Nenhum vale encontrado com os filtros aplicados.</td></tr>';
            return;
        }

        vales.forEach(vale => {
            valeDetailsData[vale.n_vale] = vale.registro_baixas_detalhadas;

            // Calcula o status dinamicamente para garantir consistência
            const saldo = parseInt(vale.quantidade_emitida) - parseInt(vale.quantidade_baixada);
            const statusCalculado = saldo > 0 ? 'Aberto' : 'Fechado';
            const statusClasses = { 'Aberto': 'bg-success', 'Fechado': 'bg-secondary' };
            const badgeClass = statusClasses[statusCalculado];

            const tr = document.createElement('tr');
            tr.insertCell().textContent = vale.n_vale;
            tr.insertCell().textContent = formatDate(vale.data_emissao);
            tr.insertCell().innerHTML = vale.nome_razao_social || '<span class="text-muted fst-italic">N/A</span>';
            tr.insertCell().textContent = vale.tipo_palete;
            tr.insertCell().textContent = vale.quantidade_emitida;
            tr.insertCell().textContent = vale.quantidade_baixada;
            tr.insertCell().innerHTML = `<span class="badge ${badgeClass}">${statusCalculado}</span>`;
            
            const actionsCell = tr.insertCell();
            actionsCell.style.whiteSpace = 'nowrap'; // Impede que os botões quebrem a linha

            const detailsButton = document.createElement('button');
            detailsButton.className = 'btn btn-sm btn-info me-2';
            detailsButton.innerHTML = '<i class="fas fa-eye"></i> Detalhes';
            detailsButton.addEventListener('click', () => openDetalhesModal(vale.n_vale));

            const pdfButton = document.createElement('button');
            pdfButton.className = 'btn btn-sm btn-danger';
            pdfButton.innerHTML = '<i class="fas fa-file-pdf"></i> PDF';
            pdfButton.addEventListener('click', () => window.open(`/vale-palete/api/vales/imprimir_vale.php?n_vale=${vale.n_vale}`, '_blank'));

            actionsCell.append(detailsButton, pdfButton);
            
            tbody.appendChild(tr);
        });

        // 5. Atribui a função de exportação ao botão
        const exportCsvButton = document.getElementById('exportCsvButton');
        if (exportCsvButton) exportCsvButton.onclick = () => exportToCsv(vales);

        const exportPdfButton = document.getElementById('exportPdfButton');
        if (exportPdfButton) exportPdfButton.onclick = () => exportToPdf(vales);

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Erro ao carregar histórico. Verifique o console para detalhes.</td></tr>`;
        console.error('Falha ao carregar histórico:', error);
    }
}

async function loadValesParaBaixa() {
    const tbody = document.getElementById('valesParaBaixaTableBody');
    if (!tbody) {
        console.error("Elemento 'valesParaBaixaTableBody' não encontrado.");
        return;
    }
    const searchValue = document.getElementById('searchBaixarVale').value;
    const url = `/vale-palete/api/vales/listarAbertos.php?search=${encodeURIComponent(searchValue)}`;
    tbody.innerHTML = '<tr><td colspan="9" class="text-center">Carregando...</td></tr>';
    try {
        const response = await fetch(url);
        const vales = await response.json();
        tbody.innerHTML = '';
        if (vales.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">Nenhum vale aberto para baixa.</td></tr>';
            return;
        }
        vales.forEach(vale => {
            const saldo = vale.quantidade_emitida - vale.quantidade_baixada;
            const tr = document.createElement('tr');
            tr.insertCell().textContent = vale.n_vale;
            tr.insertCell().textContent = formatDate(vale.data_emissao);
            tr.insertCell().textContent = vale.nome_razao_social;
            tr.insertCell().textContent = vale.tipo_palete;
            tr.insertCell().textContent = vale.quantidade_emitida;
            tr.insertCell().textContent = vale.quantidade_baixada;
            tr.insertCell().innerHTML = `<strong>${saldo}</strong>`;
            tr.insertCell().innerHTML = `<span class="badge bg-success">${vale.status_vale}</span>`;
            
            const actionsCell = tr.insertCell();
            actionsCell.style.whiteSpace = 'nowrap'; // Impede que os botões quebrem a linha

            const baixarButton = document.createElement('button');
            baixarButton.className = 'btn btn-sm btn-primary me-2';
            baixarButton.innerHTML = '<i class="fas fa-download"></i> Baixar';
            baixarButton.addEventListener('click', () => openBaixarModal(vale.n_vale, saldo));

            const pdfButton = document.createElement('button');
            pdfButton.className = 'btn btn-sm btn-danger';
            pdfButton.innerHTML = '<i class="fas fa-file-pdf"></i> PDF';
            pdfButton.addEventListener('click', () => window.open(`/vale-palete/api/vales/imprimir_vale.php?n_vale=${vale.n_vale}`, '_blank'));
            actionsCell.append(baixarButton, pdfButton);

            tbody.appendChild(tr);
        });
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">Erro ao carregar vales.</td></tr>';
        console.error('Falha ao carregar vales para baixa:', error);
    }
}

async function loadClientesTable() {
    const tbody = document.getElementById('clientesTableBody');
    if (!tbody) {
        console.error("Elemento 'clientesTableBody' não encontrado.");
        return;
    }
    tbody.innerHTML = '<tr><td colspan="3" class="text-center">Carregando...</td></tr>';
    try {
        const response = await fetch('/vale-palete/api/clientes/listar.php');
        const clientes = await response.json();
        tbody.innerHTML = '';
        clientes.forEach(cliente => {
            const tr = document.createElement('tr');
            tr.insertCell().textContent = cliente.cpf_cnpj;
            tr.insertCell().textContent = cliente.nome_razao_social;
            
            const actionsCell = tr.insertCell();
            actionsCell.style.whiteSpace = 'nowrap'; 

            const editButton = document.createElement('button');
            editButton.className = 'btn btn-sm btn-secondary me-2';
            editButton.innerHTML = '<i class="fas fa-edit"></i> Editar';
            editButton.addEventListener('click', () => openEditClienteModal(cliente.cpf_cnpj, cliente.nome_razao_social));

            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-sm btn-danger';
            deleteButton.innerHTML = '<i class="fas fa-trash"></i> Excluir';
            deleteButton.addEventListener('click', () => deleteCliente(cliente.cpf_cnpj, cliente.nome_razao_social));
            actionsCell.append(editButton, deleteButton);

            tbody.appendChild(tr);
        });
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Erro ao carregar clientes.</td></tr>';
        console.error('Falha ao carregar tabela de clientes:', error);
    }
}

async function loadTiposPaleteParaSelect() {
    const select = document.getElementById('tipoPalete');
    if (!select) {
        console.error("Elemento 'tipoPalete' não encontrado.");
        return;
    }
    try {
        const response = await fetch('/vale-palete/api/paletes/listar.php');
        const paletes = await response.json();
        select.innerHTML = '<option value="">Selecione um tipo...</option>';
        paletes.forEach(palete => {
            const option = document.createElement('option');
            option.dataset.valor = palete.valor; // Armazena o valor bruto
            option.value = palete.tipo_palete;
            option.textContent = palete.tipo_palete;
            select.appendChild(option);
        });
    } catch (error) {
        select.innerHTML = '<option value="">Erro ao carregar</option>';
        console.error('Falha ao carregar tipos de palete:', error);
    }
}

async function loadTiposPaleteTable() {
    const tbody = document.getElementById('tiposPaleteTableBody');
    if (!tbody) {
        console.error("Elemento 'tiposPaleteTableBody' não encontrado.");
        return;
    }
    tbody.innerHTML = '<tr><td colspan="3" class="text-center">Carregando...</td></tr>';
    try {
        const response = await fetch('/vale-palete/api/paletes/listar.php');
        const paletes = await response.json();
        tbody.innerHTML = '';
        if (paletes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">Nenhum tipo de palete cadastrado.</td></tr>';
            return;
        }
        paletes.forEach(palete => {
            const tr = document.createElement('tr');
            const valorFormatado = parseFloat(palete.valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            
            tr.insertCell().textContent = palete.tipo_palete;
            tr.insertCell().textContent = valorFormatado;

            const actionsCell = tr.insertCell();
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-sm btn-danger';
            deleteButton.innerHTML = '<i class="fas fa-pallet"></i> Excluir';
            deleteButton.addEventListener('click', () => deleteTipoPalete(palete.id, palete.tipo_palete));
            actionsCell.appendChild(deleteButton);

            tbody.appendChild(tr);
        });
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Erro ao carregar tipos de palete.</td></tr>';
        console.error('Falha ao carregar tabela de tipos de palete:', error);
    }
}

async function loadConfiguracoes() {
    try {
        const response = await fetch('/vale-palete/api/config/get.php');
        const configs = await response.json();
        const nextValeInput = document.getElementById('nextValeNumberConfig');
        if (nextValeInput && configs.nextValeNumber) {
            nextValeInput.value = configs.nextValeNumber;
        }
    } catch (error) {
        showMessage('danger', 'Erro ao carregar configurações.');
        console.error('Falha ao carregar configurações:', error);
    }
}

// --- FUNÇÕES DE MANIPULAÇÃO DE EVENTOS (HANDLERS) ---

async function handleEmitirVale(event) {
    event.preventDefault();
    const form = event.target;

    // Validação para garantir que um cliente válido foi encontrado
    const hiddenCpfField = document.getElementById('hiddenCpfCnpj');
    if (!hiddenCpfField.value) {
        showMessage('danger', 'Cliente inválido. Por favor, busque e selecione um cliente válido antes de emitir.');
        return;
    }

    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/vale-palete/api/vales/emitir.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            form.reset();
            setInitialEmitirFormValues();
            loadHistorico(); // Atualiza o histórico

            // Abre a página de impressão em uma nova aba
            window.open(`/vale-palete/api/vales/imprimir_vale.php?n_vale=${result.n_vale}`, '_blank');

            loadValesParaBaixa(); // Atualiza a tela de baixa
        } else {
            throw new Error(result.message || 'Ocorreu um erro desconhecido.');
        }
    } catch (error) {
        showMessage('danger', `Erro ao emitir vale: ${error.message}`);
        console.error('Falha na emissão do vale:', error);
    }
}

async function handleAddCliente(event) {
    event.preventDefault();
    const form = event.target;
    const data = Object.fromEntries(new FormData(form).entries());
    
    try {
        const response = await fetch('/vale-palete/api/clientes/cadastrar.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            form.reset();
            loadClientesTable(); // Atualiza a tabela
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        showMessage('danger', `Erro ao cadastrar cliente: ${error.message}`);
    }
}

async function handleBaixarVale(event) {
    event.preventDefault();
    const form = event.target;
    const data = Object.fromEntries(new FormData(form).entries());

    try {
        const response = await fetch('/vale-palete/api/vales/baixar.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            bootstrap.Modal.getInstance(document.getElementById('baixarValeModal')).hide();
            loadValesParaBaixa(); // Recarrega a lista de vales para baixa
            loadHistorico(); // Recarrega o histórico
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        document.getElementById('modalMensagemErro').textContent = error.message;
        document.getElementById('modalMensagemErro').style.display = 'block';
    }
}

async function handleAddTipoPalete(event) {
    event.preventDefault();
    const form = event.target;
    const data = Object.fromEntries(new FormData(form).entries());

    try {
        const response = await fetch('/vale-palete/api/paletes/cadastrar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            form.reset();
            loadTiposPaleteTable();
            loadTiposPaleteParaSelect();
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        showMessage('danger', `Erro ao cadastrar tipo de palete: ${error.message}`);
    }
}

async function deleteTipoPalete(id, nome) {
    if (!confirm(`Tem certeza que deseja excluir o tipo de palete "${nome}"? Esta ação não pode ser desfeita.`)) {
        return;
    }

    try {
        const response = await fetch('/vale-palete/api/paletes/excluir.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: id })
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            loadTiposPaleteTable();
            loadTiposPaleteParaSelect();
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        showMessage('danger', `Erro ao excluir: ${error.message}`);
    }
}

async function deleteCliente(cpfCnpj, nome) {
    if (!confirm(`Tem certeza que deseja excluir o cliente "${nome}"? Esta ação não pode ser desfeita.`)) {
        return;
    }

    try {
        const response = await fetch('/vale-palete/api/clientes/excluir.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ cpf_cnpj: cpfCnpj })
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            loadClientesTable();
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        showMessage('danger', `Erro ao excluir: ${error.message}`);
    }
}

async function handleUpdateCliente(event) {
    event.preventDefault();
    const form = event.target;
    const data = Object.fromEntries(new FormData(form).entries());

    try {
        const response = await fetch('/vale-palete/api/clientes/editar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            bootstrap.Modal.getInstance(document.getElementById('editClienteModal')).hide();
            loadClientesTable(); // CORREÇÃO: Atualizar a tabela de clientes, não a de baixa.
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        // Idealmente, mostraríamos o erro dentro do modal
        showMessage('danger', `Erro ao atualizar cliente: ${error.message}`);
        console.error('Falha ao atualizar cliente:', error);
    }
}

async function handleSaveConfiguracoes(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/vale-palete/api/config/update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok && result.success) {
            showMessage('success', result.message);
            setInitialEmitirFormValues();
        } else {
            throw new Error(result.message || 'Erro desconhecido.');
        }
    } catch (error) {
        showMessage('danger', `Erro ao salvar configurações: ${error.message}`);
    }
}

async function handleClientLookup(event) {
    const searchTerm = event.target.value;
    const nameField = document.getElementById('lookupNomeRazaoSocial');
    const hiddenCpfField = document.getElementById('hiddenCpfCnpj');

    if (searchTerm.trim() === '') {
        nameField.value = '';
        hiddenCpfField.value = '';
        return;
    }

    try {
        const response = await fetch(`/vale-palete/api/clientes/buscar.php?term=${encodeURIComponent(searchTerm)}`);
        const cliente = await response.json();

        if (cliente) {
            nameField.value = cliente.nome_razao_social;
            hiddenCpfField.value = cliente.cpf_cnpj;
        } else {
            nameField.value = 'Cliente não encontrado...';
            hiddenCpfField.value = '';
        }
    } catch (error) {
        console.error('Erro ao buscar cliente:', error);
        nameField.value = 'Erro na busca.';
        hiddenCpfField.value = '';
    }
}

function debounce(func, delay) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), delay);
    };
}

// --- FUNÇÕES AUXILIARES E DE UI ---

function updatePageTitle() {
    const pageTitleEl = document.getElementById('pageTitle');
    const activeTab = document.querySelector('.sidebar-menu .nav-link.active');
    if (pageTitleEl && activeTab) {
        // .textContent pega o texto visível, ignorando o ícone
        pageTitleEl.textContent = activeTab.textContent.trim();
    }
}

async function setInitialEmitirFormValues() {
    // Função auxiliar para definir valor de um campo de forma segura
    const setSafeValue = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.value = value;
        }
    };

    const form = document.getElementById('formEmitirVale');
    if (form) form.reset();

    const dataEmissao = document.getElementById('dataEmissao');
    if (dataEmissao) dataEmissao.valueAsDate = new Date();

    setSafeValue('lookupCpfCnpj', '');
    setSafeValue('lookupNomeRazaoSocial', '');
    setSafeValue('hiddenCpfCnpj', '');
    setSafeValue('valorUnitario', '');
    setSafeValue('valorTotal', '');

    try {
        const response = await fetch('/vale-palete/api/config/get.php');
        const configs = await response.json();
        setSafeValue('nVale', configs.formattedValeNumber || configs.nextValeNumber || '');
    } catch (error) {
        console.error('Falha ao buscar próximo número do vale:', error);
    }
}

function updateValores() {
    const tipoPaleteSelect = document.getElementById('tipoPalete');
    const quantidadeInput = document.getElementById('quantidadeEmitida');
    const valorUnitarioInput = document.getElementById('valorUnitario');
    const valorTotalInput = document.getElementById('valorTotal');

    const selectedOption = tipoPaleteSelect.options[tipoPaleteSelect.selectedIndex];
    const valorUnitario = selectedOption.dataset.valor ? parseFloat(selectedOption.dataset.valor) : 0;
    const quantidade = parseInt(quantidadeInput.value) || 0;

    const valorTotal = valorUnitario * quantidade;

    valorUnitarioInput.value = valorUnitario > 0 ? valorUnitario.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '';
    valorTotalInput.value = valorTotal > 0 ? valorTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '';
}

function openBaixarModal(nVale, saldo) {
    const modalEl = document.getElementById('baixarValeModal');
    const modal = new bootstrap.Modal(modalEl);

    document.getElementById('formBaixarVale').reset();
    document.getElementById('modalNVale').value = nVale;
    document.getElementById('modalNValeDisplay').textContent = nVale;
    document.getElementById('modalSaldo').textContent = saldo;
    document.getElementById('quantidadeBaixar').max = saldo;
    document.getElementById('modalMensagemErro').style.display = 'none';

    modal.show();
}

function openDetalhesModal(nVale) {
    const modalEl = document.getElementById('detalhesValeModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalTitle = document.getElementById('detalhesValeModalLabel');
    const modalBody = document.getElementById('detalhesValeBody');

    modalTitle.textContent = `Histórico de Baixas - Vale Nº ${nVale}`;

    const registrosData = valeDetailsData[nVale];
    let registros = [];

    try {
        // Garante que os dados sejam processados corretamente, seja string JSON ou já um objeto/array.
        if (typeof registrosData === 'string' && registrosData.trim().startsWith('[')) {
            registros = JSON.parse(registrosData);
        } else if (Array.isArray(registrosData)) {
            registros = registrosData; // Já é um array, use diretamente.
        }
        // Se for null, undefined ou outro formato, 'registros' continuará como um array vazio.

        if (registros.length === 0) {
            modalBody.innerHTML = '<p class="text-center">Nenhuma baixa registrada para este vale.</p>';
            modal.show();
            return;
        }

        let tableHtml = `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Data da Baixa</th>
                        <th>Quantidade</th>
                        <th>Usuário</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
        `;

        registros.forEach(baixa => {
            const dataFormatada = baixa.data ? `${formatDate(baixa.data.split(' ')[0])} ${baixa.data.split(' ')[1]}` : 'N/A';
            tableHtml += `
                <tr>
                    <td>${dataFormatada}</td>
                    <td>${baixa.quantidade}</td>
                    <td>${baixa.usuario}</td>
                    <td>${baixa.descricao || '-'}</td>
                </tr>
            `;
        });

        tableHtml += `</tbody></table>`;
        modalBody.innerHTML = tableHtml;
    } catch (e) {
        modalBody.innerHTML = '<p class="text-center text-danger">Erro ao processar os detalhes das baixas.</p>';
        console.error("Erro ao processar JSON de baixas:", e, registrosData);
    }

    modal.show();
}

function openEditClienteModal(cpfCnpj, nome) {
    const modalEl = document.getElementById('editClienteModal');
    const modal = new bootstrap.Modal(modalEl);

    document.getElementById('editCpfCnpj').value = cpfCnpj;
    document.getElementById('editNomeRazaoSocial').value = nome;

    modal.show();
}

function showMessage(type, message) {
    const messageArea = document.getElementById('messageArea');
    messageArea.className = `alert alert-${type}`;
    messageArea.textContent = message;
    messageArea.style.display = 'block';
    setTimeout(() => {
        messageArea.style.display = 'none';
    }, 5000);
}

function formatDate(dataISO) {
    if (!dataISO) return '';
    const [ano, mes, dia] = dataISO.split('-');
    return `${dia}/${mes}/${ano}`;
}

function exportToCsv(data) {
    if (data.length === 0) {
        showMessage('warning', 'Não há dados para exportar.');
        return;
    }

    const headers = ['N_Vale', 'Data_Emissao', 'Cliente', 'Tipo_Palete', 'Qtd_Emitida', 'Qtd_Baixada', 'Status'];
    const rows = data.map(vale => {
        const saldo = parseInt(vale.quantidade_emitida) - parseInt(vale.quantidade_baixada);
        const statusCalculado = saldo > 0 ? 'Aberto' : 'Fechado';
        return [
            vale.n_vale,
            formatDate(vale.data_emissao),
            `"${(vale.nome_razao_social || '').replace(/"/g, '""')}"`, // Trata aspas no nome
            vale.tipo_palete,
            vale.quantidade_emitida,
            vale.quantidade_baixada,
            statusCalculado
        ];
    });

    let csvContent = "data:text/csv;charset=utf-8," 
        + headers.join(',') + '\n' 
        + rows.map(e => e.join(',')).join('\n');

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "historico_vales.csv");
    document.body.appendChild(link);

    link.click();
    document.body.removeChild(link);
}

function exportToPdf(data) {
    if (!data || data.length === 0) {
        showMessage('warning', 'Não há dados para gerar o PDF.');
        return;
    }

    // Garante que as bibliotecas estão carregadas
    if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
        showMessage('danger', 'A biblioteca jsPDF não está carregada.');
        console.error('jsPDF não encontrado. Verifique a inclusão da biblioteca no HTML.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Título do Documento
    doc.setFontSize(18);
    doc.text('Relatório de Histórico de Vales', 14, 22);
    doc.setFontSize(11);
    doc.setTextColor(100);
    doc.text(`Relatório gerado em: ${new Date().toLocaleDateString('pt-BR')}`, 14, 30);

    // Definir colunas e linhas para a tabela
    const head = [['Nº Vale', 'Emissão', 'Cliente', 'Tipo Palete', 'Emitida', 'Baixada', 'Status']];
    const body = data.map(vale => {
        const saldo = parseInt(vale.quantidade_emitida) - parseInt(vale.quantidade_baixada);
        const statusCalculado = saldo > 0 ? 'Aberto' : 'Fechado';
        return [
            vale.n_vale,
            formatDate(vale.data_emissao),
            vale.nome_razao_social || 'N/A',
            vale.tipo_palete,
            vale.quantidade_emitida,
            vale.quantidade_baixada,
            statusCalculado
        ];
    });

    // Gerar a tabela com o plugin autoTable
    doc.autoTable({
        head: head,
        body: body,
        startY: 35,
        theme: 'striped',
        headStyles: { fillColor: [16, 44, 75] }, // Cor do cabeçalho #102c4b
        styles: { fontSize: 8 },
    });

    // Salvar o PDF
    const timestamp = new Date().toISOString().slice(0, 19).replace(/[-T:]/g, '');
    doc.save(`historico_vales_${timestamp}.pdf`);
}
