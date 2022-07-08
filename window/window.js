let isOpened = false;

const openWindow = function (settings, done){
    if(isOpened) return false;

    const title = settings['title'], //type: object|string|undefined
        context = settings['context']; //type: string
    if(typeof context !== 'string') console.error(context);

    const windowThing = $('<div id="modal_back" style="display: none"></div>').append('<div id="window"></div>');

    let titleThing = $('<div id="title_box"></div>');
    if(typeof title === 'object'){
        const titleObj = settings['title'],
            text = titleObj['text'],
            icon = titleObj['icon'], //type: string, path rel to img
            color = titleObj['color'] ?? '#1a1919',
            backColor = titleObj['back_color'] ?? '#d0d0d0',
            hasCloseButton = titleObj['close_button'] ?? true;

        titleThing.css('color', color).css('background-color', backColor);
        if(typeof icon !== 'undefined' && icon !== false)
            titleThing.append('<img id="title_icon" src="/img/'+icon+'">');
        titleThing.append('<span id="title_text">'+text+'</span>');
        if(hasCloseButton)
            $('<a id="close_button" onclick="closeWindow()"></a>').appendTo(titleThing)
                .append('<img src="/img/pages/icons/close.png">');
        finish();
    }
    else if(typeof title === 'string'){ //type: string, path rel to window
        $.get('window/'+title).done((content)=>{
            titleThing.append($(content));
            finish();
        });
    }
    else if (typeof title === 'undefined' && title === false){
        titleThing = '';
        finish();
    }
    else console.error(title);

    function finish(){
        $.get('window/'+context).done((content)=>{
            const windowObj = windowThing.children('#window');
            if (titleThing!=='')
                windowObj.append(titleThing).append('<div id="title_separator"></div>');
            windowObj.append('<div id="content_box"></div>').children('#content_box').append($(content));
            $('body').append('<link href="/css/window.css" rel="stylesheet"/>').append(windowThing);
            windowThing.fadeIn('fast', ()=>{
                isOpened=true;
                if(typeof done==='function') done();
            });
        })
    }
    return true;
}

const closeWindow = function (done){
    if (!isOpened) return false;
    $('#modal_back').fadeOut('fast', ()=>{
        $('#modal_back').remove();
        isOpened = false;
        if(typeof done==='function') done();
    });
    return true;
}
