<?php
    
return [
    'adminEmail' => 'admin@example.com',

    /*** Tipos de estados para los registros ***/
    'statusTodoNumber' => [
        0 => 'Inactivo',
        10 => 'Activo',
        11 => 'Finalizado',

        # Estados distribución y envio
        6 => 'Listo para enviar',       
        7 => 'Pendiente por entregar',
        8 => 'Entregado',
        9 => 'Devuelto',        

        # Estado de archivo
        12 => 'Archivado',

        # Estados de transferencia
        13 => 'Pendiente por transferir',
        14 => 'Transferencia aceptada',
        15 => 'Transferencia rechazada',

        # Estado de PQRS
        16 => 'Devuelto al ciudadano',

        # Estados de prestamos documentales
        18 => 'Solicitud de Préstamo',
        19 => 'Préstamo Aprobado',
        20 => 'Préstamo Cancelado',
        21 => 'Préstamo Devuelto',

        # Estado de PQRS
        22 => 'Desistir Radicado',

        # Estado vacio
        100 => ''
    ],

    'statusTodoText' => [
        'Inactivo' => 0,
        'Activo' => 10,
        'Finalizado' => 11,
        
        # Estados distribución y envio
        'ListoEnvio' => 6,
        'PendienteEntrega' => 7,
        'Entregado' => 8,
        'Devuelto' => 9,

        # Estado de archivo
        'Archivado' => 12,

        # Estados de transferencia
        'PendienteTransferir' => 13,
        'TransferenciaAceptada' => 14,
        'TransferenciaRechazada' => 15,

        # Estado de PQRS
        'DevueltoAlCiudadano' => 16,

        # Estados de prestamos documentales
        'SolicitudPrestamo' => 18,
        'PrestamoAprobado' => 19,
        'PrestamoCancelado' => 20,
        'PrestamoDevuelto' => 21,

        # Estado de PQRS
        'DesistirRadicado' => 22,
        
        # Estado vacio
        '' => 100
    ],
];


