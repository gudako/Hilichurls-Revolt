let isOpened = false;

const openWindow = function (title, contextPath, closable=true){
    $('body').append('<div id="loader"> </div>');
    $('#loader').load('window/window.html', ()=>{
        $('#content_box').load('window/'+contextPath, ()=>{

            const xButton = $('#title_box>a');
            $('#title_box>span').text(title);
            if (!closable) xButton.remove();
            xButton.click(closeWindow);

            $('#modal_back').fadeIn('fast', ()=>isOpened=true)
        })
    });
}

const closeWindow = function (){
    if (!isOpened) return;
    $('#modal_back').fadeOut('fast', ()=>{
        $('#loader').remove();
        isOpened = false;
    })
}
