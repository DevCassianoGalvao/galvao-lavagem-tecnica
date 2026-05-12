<?php

$crmMetrics = [
    ['label' => 'Leads ativos', 'value' => '42', 'delta' => '+18%', 'tone' => 'gold'],
    ['label' => 'Servicos agendados', 'value' => '16', 'delta' => '7 dias', 'tone' => 'green'],
    ['label' => 'Recorrencia', 'value' => '31%', 'delta' => '+6%', 'tone' => 'blue'],
    ['label' => 'Ticket medio', 'value' => 'R$ 860', 'delta' => 'premium', 'tone' => 'gold'],
];

$crmTags = [
    ['id' => 1, 'name' => 'Premium', 'color' => '#D4AF37', 'count' => 18],
    ['id' => 2, 'name' => 'Recorrencia', 'color' => '#72C78F', 'count' => 12],
    ['id' => 3, 'name' => 'Condominio', 'color' => '#73A7FF', 'count' => 9],
    ['id' => 4, 'name' => 'Urgente', 'color' => '#EF6F6C', 'count' => 5],
    ['id' => 5, 'name' => 'IA visual', 'color' => '#C8A95B', 'count' => 22],
];

$crmLeads = [
    [
        'id' => 101,
        'client' => 'Marina Albuquerque',
        'phone' => '5511999991111',
        'property' => 'Residencia Jardim Europa',
        'surface' => 'Pedra natural, garagem e muro lateral',
        'status' => 'Em analise',
        'score' => 92,
        'ai_summary' => 'Lodo moderado em area de sombra, alto potencial de recuperacao visual e prioridade estetica.',
        'tags' => ['Premium', 'IA visual'],
        'images' => ['Pedra', 'Muro', 'Acesso'],
    ],
    [
        'id' => 102,
        'client' => 'Condominio Villa Serena',
        'phone' => '5511988882222',
        'property' => 'Area comum externa',
        'surface' => 'Calcada, piscina e area gourmet',
        'status' => 'Proposta enviada',
        'score' => 86,
        'ai_summary' => 'Manutencao preventiva recomendada para reduzir recorrencia de musgo em bordas e acessos.',
        'tags' => ['Condominio', 'Recorrencia'],
        'images' => ['Piscina', 'Calcada', 'Deck'],
    ],
    [
        'id' => 103,
        'client' => 'Atelier Brava',
        'phone' => '5511977773333',
        'property' => 'Comercio fachada rua',
        'surface' => 'Fachada e calcada',
        'status' => 'Novo diagnostico',
        'score' => 74,
        'ai_summary' => 'Desgaste visual na primeira impressao. Indicado diagnostico presencial para acabamento.',
        'tags' => ['Comercio', 'Urgente'],
        'images' => ['Fachada', 'Entrada'],
    ],
];

$kanbanColumns = [
    ['id' => 'novo-lead', 'title' => 'Novo lead', 'hint' => 'Entradas recentes'],
    ['id' => 'diagnostico', 'title' => 'Diagnostico', 'hint' => 'Analise tecnica'],
    ['id' => 'criando-proposta', 'title' => 'Criando proposta', 'hint' => 'Orcamento em preparo'],
    ['id' => 'proposta-enviada', 'title' => 'Proposta enviada', 'hint' => 'Aguardando retorno'],
    ['id' => 'negociacao', 'title' => 'Negociacao', 'hint' => 'Ajustes comerciais'],
    ['id' => 'agendado', 'title' => 'Agendado', 'hint' => 'Execucao marcada'],
    ['id' => 'concluido', 'title' => 'Concluido', 'hint' => 'Servico finalizado'],
    ['id' => 'perdido', 'title' => 'Perdido', 'hint' => 'Sem conversao'],
];

$kanbanCards = [
    [
        'id' => 301,
        'column' => 'novo-lead',
        'client' => 'Atelier Brava',
        'district' => 'Pinheiros',
        'priority' => 'Alta',
        'surface' => 'Fachada e calcada',
        'ai_summary' => 'Primeira impressao comprometida por manchas e escurecimento em area de rua.',
        'tags' => ['Comercio', 'Urgente'],
        'thumb' => 'Fachada',
    ],
    [
        'id' => 302,
        'column' => 'diagnostico',
        'client' => 'Marina Albuquerque',
        'district' => 'Jardim Europa',
        'priority' => 'Premium',
        'surface' => 'Pedra natural e garagem',
        'ai_summary' => 'Lodo moderado em area de sombra, alto potencial de revitalizacao visual.',
        'tags' => ['Premium', 'IA visual'],
        'thumb' => 'Pedra',
    ],
    [
        'id' => 303,
        'column' => 'criando-proposta',
        'client' => 'Casa Vila Nova',
        'district' => 'Moema',
        'priority' => 'Media',
        'surface' => 'Deck e area gourmet',
        'ai_summary' => 'Desgaste visual uniforme, indicado plano com manutencao preventiva.',
        'tags' => ['Recorrencia'],
        'thumb' => 'Deck',
    ],
    [
        'id' => 304,
        'column' => 'proposta-enviada',
        'client' => 'Condominio Villa Serena',
        'district' => 'Alto da Boa Vista',
        'priority' => 'Alta',
        'surface' => 'Piscina, calcada e area comum',
        'ai_summary' => 'Musgo recorrente em bordas e acessos. Proposta recomenda ciclo preventivo.',
        'tags' => ['Condominio', 'Recorrencia'],
        'thumb' => 'Piscina',
    ],
    [
        'id' => 305,
        'column' => 'negociacao',
        'client' => 'Escritorio Lume',
        'district' => 'Itaim Bibi',
        'priority' => 'Media',
        'surface' => 'Acesso externo',
        'ai_summary' => 'Cliente avaliando execucao fora do horario comercial.',
        'tags' => ['Empresa'],
        'thumb' => 'Acesso',
    ],
    [
        'id' => 306,
        'column' => 'agendado',
        'client' => 'Residencial Arvoredo',
        'district' => 'Brooklin',
        'priority' => 'Alta',
        'surface' => 'Muro e garagem',
        'ai_summary' => 'Servico agendado com foco em seguranca e recuperacao estetica.',
        'tags' => ['Agendado', 'Seguranca'],
        'thumb' => 'Muro',
    ],
    [
        'id' => 307,
        'column' => 'concluido',
        'client' => 'Casa Harmonia',
        'district' => 'Vila Madalena',
        'priority' => 'Baixa',
        'surface' => 'Calcada e entrada',
        'ai_summary' => 'Revitalizacao concluida. Cliente indicado para recorrencia trimestral.',
        'tags' => ['Concluido'],
        'thumb' => 'Entrada',
    ],
    [
        'id' => 308,
        'column' => 'perdido',
        'client' => 'Loja Aurora',
        'district' => 'Centro',
        'priority' => 'Baixa',
        'surface' => 'Fachada',
        'ai_summary' => 'Lead arquivado por falta de retorno apos proposta.',
        'tags' => ['Arquivado'],
        'thumb' => 'Fachada',
    ],
];

$crmClient = [
    'name' => 'Marina Albuquerque',
    'phone' => '5511999991111',
    'email' => 'marina.albuquerque@email.com',
    'document' => 'Cliente residencial premium',
    'address' => 'Rua Harmonia, 184 - Jardim Europa, Sao Paulo - SP',
    'property' => 'Residencia Jardim Europa',
    'coordinates' => '-23.5771, -46.6874',
    'tags' => ['Premium', 'IA visual', 'Manutencao preventiva'],
    'external_links' => [
        ['label' => 'WhatsApp', 'url' => 'https://wa.me/5511999991111'],
        ['label' => 'Maps', 'url' => 'https://www.openstreetmap.org/'],
    ],
];

$crmTimeline = [
    ['type' => 'Quiz', 'title' => 'Diagnostico tecnico recebido', 'body' => 'Cliente enviou imagens de pedra natural, garagem e muro lateral.', 'time' => 'Hoje, 09:42'],
    ['type' => 'IA', 'title' => 'Resumo visual gerado', 'body' => 'Lodo moderado, manchas por umidade e boa margem de revitalizacao estetica.', 'time' => 'Hoje, 09:44'],
    ['type' => 'Contato', 'title' => 'Follow-up agendado', 'body' => 'Retorno consultivo marcado para apresentar plano tecnico.', 'time' => 'Hoje, 10:15'],
    ['type' => 'Recorrencia', 'title' => 'Retorno preventivo previsto', 'body' => 'Sistema estimou novo contato em 6 meses para monitoramento de lodo em Nova Friburgo.', 'time' => '07/11/2026'],
    ['type' => 'Nota', 'title' => 'Observacao interna', 'body' => 'Cliente prioriza acabamento visual para area social externa.', 'time' => 'Ontem, 17:10'],
];

$crmRecurrences = [
    [
        'title' => 'Retorno preventivo',
        'due_at' => '07/11/2026',
        'interval' => '6 meses',
        'reason' => 'Media de retorno do lodo para Nova Friburgo.',
        'status' => 'Ativo',
    ],
    [
        'title' => 'Contato consultivo',
        'due_at' => '24/10/2026',
        'interval' => '14 dias antes',
        'reason' => 'Lembrete interno para preparar WhatsApp futuro.',
        'status' => 'Pendente',
    ],
];

$crmNotes = [
    ['author' => 'Operacao', 'body' => 'Verificar necessidade de protecao nos pontos de pedra mais porosa.', 'time' => '10 min atras'],
    ['author' => 'Comercial', 'body' => 'Cliente sensivel a prazo, mas busca acabamento premium.', 'time' => '1 h atras'],
];

$crmUploads = [
    ['label' => 'Pedra natural', 'type' => 'Antes', 'tone' => 'stone'],
    ['label' => 'Muro lateral', 'type' => 'Diagnostico', 'tone' => 'wall'],
    ['label' => 'Garagem', 'type' => 'IA source', 'tone' => 'floor'],
    ['label' => 'Acesso social', 'type' => 'Antes', 'tone' => 'access'],
];

$calendarCategories = [
    'visit' => ['label' => 'Visita tecnica', 'color' => '#D4AF37'],
    'service' => ['label' => 'Servico', 'color' => '#72C78F'],
    'follow_up' => ['label' => 'Follow-up', 'color' => '#73A7FF'],
    'preventive' => ['label' => 'Retorno preventivo', 'color' => '#C8A95B'],
];

$calendarEvents = [
    [
        'id' => 501,
        'date' => '2026-05-07',
        'time' => '09:00',
        'title' => 'Visita tecnica - Marina Albuquerque',
        'client' => 'Marina Albuquerque',
        'location' => 'Jardim Europa',
        'category' => 'visit',
        'status' => 'Confirmado',
    ],
    [
        'id' => 502,
        'date' => '2026-05-07',
        'time' => '14:30',
        'title' => 'Follow-up proposta Villa Serena',
        'client' => 'Condominio Villa Serena',
        'location' => 'Alto da Boa Vista',
        'category' => 'follow_up',
        'status' => 'Pendente',
    ],
    [
        'id' => 503,
        'date' => '2026-05-08',
        'time' => '08:00',
        'title' => 'Servico area gourmet',
        'client' => 'Casa Vila Nova',
        'location' => 'Moema',
        'category' => 'service',
        'status' => 'Agendado',
    ],
    [
        'id' => 504,
        'date' => '2026-05-11',
        'time' => '10:00',
        'title' => 'Retorno preventivo',
        'client' => 'Residencial Arvoredo',
        'location' => 'Brooklin',
        'category' => 'preventive',
        'status' => 'Recorrente',
    ],
    [
        'id' => 505,
        'date' => '2026-05-13',
        'time' => '15:00',
        'title' => 'Diagnostico fachada',
        'client' => 'Atelier Brava',
        'location' => 'Pinheiros',
        'category' => 'visit',
        'status' => 'Novo',
    ],
];
