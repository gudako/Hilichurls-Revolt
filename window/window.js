let isOpened = false;

const openWindow = function (title, contextPath, closable=true){
    const isSheer = typeof title !== 'string';
    let windowContent;
    let insideContent;
    $.get('window/'+isSheer?'sheer_':''+'window.html').done((content)=>{
        windowContent = content;
        $.get('window/'+contextPath).done((content1)=>{

            insideContent = content1;
            $('body').append('<div id="window_loader"></div>');
            $('#window_loader').append($(windowContent));
            $('#content_box').append($(insideContent));

            if(!isSheer){
                const xButton = $('#title_box>a');
                $('#title_box>span').text(title);
                if (!closable) xButton.remove();
                xButton.click(closeWindow);
            }
            $('#modal_back').fadeIn('fast', ()=>isOpened=true)
        });
    });
}

const closeWindow = function (){
    if (!isOpened) return;
    $('#modal_back').fadeOut('fast', ()=>{
        $('#window_loader').remove();
        isOpened = false;
    })
}
