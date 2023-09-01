(function($){

    $('.ver_radicado').click(function(e){
        e.preventDefault();
        
        const extension= $(this).data('extension');   // Extension del documento 
        const numRadicado = $(this).data('num-radicado');
        const nombreArchivo = $(this).data('nombre-archivo');        
        const data = $(this).data('contenido'); // Contenido del archivo en base 64

        let html = '';        
        const title = `Radicado Nº: <class="alerta-informativa"> <b>${numRadicado}</b>`;
        let buttons = null;

        //Se valida si es pdf u otra extension
        if(extension == 'pdf'){
                        
            // Visor de pdf
            html = '<div class="toolbar-hide"> </div>'
            html += `<embed class="visualizador" id="visualizador_pdfs" src="data:application/pdf;base64,${data}#toolbar=0" frameborder = "0" width = "100%" height = "400px">`;  

        } else {

            html = `<p>¿Está seguro de descargar el documento?.</p>`;
            buttons = {               
                cancel: {
                    label: "ACEPTAR",
                    className: 'btn-primary-orfeo',
                    callback: function(){

                        //Información del archivo en base 64
                        const dataArchivo = `data:application/${extension};base64,` + data;
                        //Crea una etiqueta <a>
                        const downloadLink = document.createElement("a");
                        const fileName = nombreArchivo;

                        downloadLink.href = dataArchivo;
                        downloadLink.download = fileName;
                        downloadLink.click();
                    }
                },
                noclose: {
                    label: "CANCELAR",
                    className: 'btn-primary-orfeo',
                    callback: function(){
                        
                    }
                },
                // ok: {
                //     label: "ACEPTO",
                //     className: 'btn-primary-orfeo',
                //     callback: function(){
                //         //descargar archivo
                //         const dataArchivo = $('#visualizador_pdfs').attr('src');
                //     }
                // }
            }; 
        }

        //Mensaje de alerta
        var dialog = bootbox.dialog({
            title: title,
            message: html,
            size: 'large',
            buttons: buttons,
        });

    });

})(jQuery);


