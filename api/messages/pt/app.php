<?php
/**
 * Message translations.
 *
 * This file is automatically generated by 'yii message/extract' command.
 * It contains the localizable messages extracted from source code.
 * You may modify this file by translating the extracted messages.
 *
 * Each array element represents the translation (value) of a message (key).
 * If the value is empty, the message is considered as not translated.
 * Messages that no longer need translation will have their translations
 * enclosed between a pair of '@@' marks.
 *
 * Message string can be used with plural forms format. Check i18n section
 * of the guide for details.
 *
 * NOTE: this file must be saved in UTF-8 encoding.
 */
$dateYear = date('Y', strtotime('+1 years'));
$yearSelect = [];
for( $i=2007; $i <= $dateYear; $i++ ){
    $yearSelect[] = ['label' => $i, 'value'=> $i ];
}

return [
    'Activate' => '',
    'Client' => '',
    'Confirm registration' => '',
    'Date' => '',
    'Login' => '',
    'N° Voucher' => '',
    'Package' => '',
    'Payment information' => '',
    'Registered payment' => '',
    'Registered payment, information below' => '',
    'Sign out' => '',
    'Value' => '',
    'botonClickRegistration' => '',
    'h2HeadMailTextRegistroPago' => '',
    'liquidacionGeneradaError' => '',
    'textBodyRegister' => '',
    'textBodyTresPagoRegistrado' => '',
    'userUpdateOk' => '',

    //Agregadas:
    'errorValidacion' => 'Erro de validação',
    'errorSesionYaIniciada' => 'O usuário já iniciou uma sessão. Saia se desejar acessar outra conta',
    'sesionIniciada' => 'Sessão iniciada',
    'errorDesencriptacion' => 'Erro ao tentar descriptografar',
    'errorTokenInvalido' => 'O token não é válido',
    'errorTokenIncorrecto' => 'O token está incorreto. Solicite a redefinição de senha novamente.',
    'successTokenValido' => 'Token válido',
    'errorDifferentPasswords' => 'Senhas não coincidem, verifique',
    'notFoundHttpException' => 'O recurso solicitado não existe.',
    'usuarioInactivo' => 'O usuário não está ativo, entre em contato com o administrador do sistema',
    //Inicio Mensajes comunes en respuesta de petición http
    'successSave' => 'Registro armazenado com sucesso',
    'successUpdate' => 'Registro atualizado com sucesso',
    'successDelete' => 'Registro excluído com sucesso',
    'successChangeStatus' => 'Status atualizado com sucesso',
    'failedMail' => 'O envio da notificação não foi bem-sucedido',
    'successMail' => 'O correio registrado foi notificado',
    'successMailResetPassword' => 'Notificação enviada ao correio registrado para redefinição de senha',
    'successPassword' => 'A senha foi alterada corretamente.',
    'searchError' => 'Erro de consulta',
    'insertError' => 'Ocorreu um erro ao salvar o registro',
    'emptyJsonSend' => 'Os parâmetros devem ser especificados',
    //Fin Mensajes comunes en respuesta de petición http    
    //Inicio mensajes para envío de correos
    'correoNoExiste' => 'O email inserido não está registrado.',
    'buttonLinkEstablecer' => 'Definir',
    'buttonLinkRestablecer' => 'Reset',
    'BienvenidoA' => 'Bem-vindo às SGD Orfeo',
    'subjectResetPassword' => 'Redefinição de senha do SGD Orfeo',
    'headMailTextRegistro' => 'Prezado Usuário',
    'headMailTextResetPassword' => 'Prezado Usuário',
    'textBodyRegistro' => 'Obrigado por se registrar em nossa plataforma. <br /> <br /> Pressione o botão Definir para criar sua senha e poder fazer login no aplicativo.',
    'textBodyResetPassword' => 'Por favor, pressione o botão Redefinir e você será direcionado para um formulário onde poderá definir uma nova senha e continuar desfrutando de nosso aplicativo.',
    //Fin mensajes para envío de correos
    'statusTodoNumber' => [
        0 => 'Inativo',
        10 => 'Ativo',
    ],
    'accessDenied' => [
        0 => 'Você não tem permissão para acessar esta página',
        1 => 'Você não tem permissões para executar esta operação',
    ],
    //mensajes de usuarios
    'Inactive User' => 'O usuário está inativo.',
    'Incorrect username or password' => 'Nome de usuário ou senha incorretos',
    'errorMaxIntentosLogueo' => 'O usuário excedeu o número máximo de tentativas com falha. Selecione a opção para recuperar a senha',
    'documentoUserExistente' => 'O documento {value} já está registrado no aplicativo',
    'userExistente' => 'O usuário já existe',
    'correoExistente' => 'O email já está registrado',
    //fin mensajes de usuarios
    //Inicio mensajes de modulos de configuración
    'grandesContribuyentesExistente' => 'A combinação do número nit e a data de pagamento inserida já existe no sistema',
    'preguntaExistente' => 'A pergunta inserida já está registrada no sistema para esta categoria',
    'cuota3Existente' => 'A combinação do número nit e a data de pagamento inserida já existe no sistema',
    'declaracionPagoExistente' => 'A combinação do número nit e a data de pagamento inserida já existe no sistema',
    'historicoUvtExistente' => 'O valor inserido já está registrado no ano indicado',
    //Fin mensajes de modulos de configuración
    //Inicio mensajes de renta
    'rentaNoExistente' => 'Selecione uma renda válida',
    'rentCloneExito' => 'O aluguel foi clonado com sucesso',
    'rentDirPermission' => 'Você não tem permissões para gerar um diretório de aluguel',
    'rentSinBalance' => 'O aluguel não tem um saldo cobrado',
    'parametrosInvalidos' => 'Você deve especificar bem os parâmetros',
    'errorSinCargaRentaAnterior' => 'Para continuar, você deve entrar no módulo de aluguel do ano anterior',
    'errorProcesoAnexo5A' => 'Não é possível processar o anexo 5',
    'errorRenglonNoUpdate' => 'A linha não é modificável',
    'errorInputRenglonNoUpdate' => 'O campo da linha não é modificável',
    'errorRentaInactiva' =>'Você não pode alterar valores de uma renda inativa',
    //Fin mensajes de renta
    //Inicio mensajes de formularios dinámicos
    'valMinimoRegistros' => 'Você deve inserir pelo menos um registro',
    'recordNoFound' => 'Registro não encontrado na posição ',
    'seProcesaron' => 'processados ',
    ' registro(s): ' => ' record(s): ',
    ' actualizado(s) y ' => ' atualizado(s) e ',
    ' nuevo(s)' => ' novo (s) ',
    'paramsRequired' => 'Você deve declarar as variáveis necessárias',
    'unprocessableForm' => 'O formulário não pode ser processado',
    'valDataArray' => 'Os dados a serem processados devem ser uma matriz de dados',
    'notExistFirstCuota' => 'Uma data de pagamento não foi definida para a primeira parcela com os dois últimos dígitos do número de identificação do cliente.',
    'notExistSecondCuota' => 'Uma data de pagamento não foi definida para a segunda parcela com os dois últimos dígitos do número de identificação do cliente.',
    'notExistHistoricoUvt' => 'Não existe valor histórico de UVT configurado para o ano do aluguel',
    'errorRequiredFechaDeclaracion' => 'Você deve inserir a data em que enviou a declaração',
    'errorlessFechaDeclaracion' => 'A data de envio deve ser maior que a data de validade',
    //Fin mensajes de formularios dinámicos

    /*** Config para los formularios dinamicos ***/
    /*********************************************/
    'anexos7A' => [
        0 => [
            'key'  => 'idAnexo7A',
            'type' => 'hidden',
            'defaultValue' => '',
            'templateOptions' => [
                'label' => '',
                'placeholder' => '',
                'required' => false,
            ]
        ],
        1 => [
            'key' => 'anioOcurrenciaAnexo7A',
            'type' => 'date',
            'defaultValue' => '',
            'templateOptions' => [
                'label' => 'Año de ocurrencia',
                'placeholder' => 'Año de ocurrencia ',
                'required' => true,
            ]
        ],

        2 => [
            'key' => 'valorRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Valor original renta',
                'placeholder' => 'Ingrese el valor original - renta',
                'required' => true,
            ]
        ],
        3 => [
            'key' => 'valorCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Valor original cree',
                'placeholder' => 'Ingrese el valor original - cree',
                'required' => true,
            ]
        ],
        4 => [
            'key' => 'perdidaRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Pérdida renta',
                'placeholder' => 'Pérdida proveniente incrngo declarada en el año - renta',
                'required' => true,
            ]
        ],
        5 => [
            'key' => 'perdidaCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Pérdida cree',
                'placeholder' => 'Pérdida proveniente incrngo declarada en el año - cree',
                'required' => true,
            ]
        ],
        6 => [
            'key' => 'perdidaCompensacionRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Pérdida o exceso sujeto a compoensación - renta',
                'placeholder' => 'Ingrese pérdida o exceso sujeto a compensación - renta',
                'required' => true,
            ]
        ],
        7 => [
            'key' => 'perdidaCompensacionCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Pérdida o exceso sujeto a compoensación - cree',
                'placeholder' => 'Ingrese pérdida o exceso sujeto a compensación - cree',
                'required' => true,
            ]
        ],
        8 => [
            'key' => 'reajusteAcumladoRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Reajustes acumulados a 2016 - renta',
                'placeholder' => 'Ingrese el valor de reajustes acumulados a 2016 - renta',
                'required' => true,
            ]
        ],
        9 => [
            'key' => 'reajusteAcumladoCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Reajustes acumulados a 2016 - cree',
                'placeholder' => 'Ingrese el valor de reajustes acumulados a 2016 - cree',
                'required' => true,
            ]
        ],
        10 => [
            'key' => 'amortizacionAnteRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Amortización efectuada acumulada al año anterior - renta',
                'placeholder' => 'Ingrese el valor de amortización efectuada acumulada al año anterior - renta',
                'required' => true,
            ]
        ],
        11 => [
            'key' => 'amortizacionAnteCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Amortización efectuada acumulada al año anterior - cree',
                'placeholder' => 'Ingrese el valor de amortización efectuada acumulada al año anterior - cree',
                'required' => true,
            ]
        ],
        12 => [
            'key' => 'saldoxAmortizarRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Saldo por amortizar inicial - renta',
                'placeholder' => 'Ingrese el valor de saldo por amortizar inicial - renta',
                'required' => true,
            ]
        ],
        13 => [
            'key' => 'SaldoxAmortizarCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'saldo por amortizar inicial - cree',
                'placeholder' => 'Ingrese el valor de saldo por amortizar inicial - cree',
                'required' => true,
            ]
        ],
        14 => [
            'key' => 'valorSusceptibleRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Valor susceptible de ser compensado a partir de 2017 (transición l.1819/2016) - renta',
                'placeholder' => 'Ingrese el valor susceptible de ser compensado a partir de 2017 (transición l.1819/2016) - renta',
                'required' => true,
            ]
        ],
        15 => [
            'key' => 'valorSusceptibleCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Valor susceptible de ser compensado a partir de 2017 (transición l.1819/2016) - cree',
                'placeholder' => 'Ingrese el valor susceptible de ser compensado a partir de 2017 (transición l.1819/2016) - cree',
                'required' => true,
            ]
        ],

        16 => [
            'key' => 'amortizacionGravableRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Amortización del año grabable - renta',
                'placeholder' => 'Ingrese la amortización del año grabable - renta',
                'required' => true,
            ]
        ],
        17 => [
            'key' => 'amortizacionGravableCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Amortización del año grabable - cree',
                'placeholder' => 'Ingrese la amortización del año grabable - cree',
                'required' => true,
            ]
        ],
        18 => [
            'key' => 'saldoNoAmortizableRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Saldo no amortizable en años siguientes - renta',
                'placeholder' => 'Ingrese la saldo no amortizable en años siguientes - renta',
                'required' => true,
            ]
        ],
        19 => [
            'key' => 'saldoNoAmortizableCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Saldo no amortizable en años siguientes - cree',
                'placeholder' => 'Ingrese la saldo no amortizable en años siguientes - cree',
                'required' => true,
            ]
        ],

        20 => [
            'key' => 'saldoxAmortizarNextRentaAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Saldo por amortizar en años siguientes - renta',
                'placeholder' => 'Ingrese la saldo por amortizar en años siguientes - renta',
                'required' => true,
            ]
        ],
        21 => [
            'key' => 'saldoxAmortizarNextCreeAnexo7A',
            'type' => 'input',
            'templateOptions' => [
                'label' => 'Saldo por amortizar en años siguientes - cree',
                'placeholder' => 'Ingrese la saldo por amortizar en años siguientes - cree',
                'required' => true,
            ]
        ],
    ]
];
