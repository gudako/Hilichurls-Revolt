/**
 * Represents a modal window. Only one instance can be opened on a single page.
 * @property {boolean} #opened - Whether the window is currently in opened state.
 * @property {undefined|string|Object} #title - The window's title, see details in {@link createTitleSetting}.
 * @property {string} #context - The window's context file link.
 */
class ModelWindow{

    #opened = false;
    #title;
    #context;

    /**
     * Represents a model window.
     * @constructor
     * @param {undefined|string|Object} title - If this is undefined, the window has no title;
     * If this is a string, the title will be loaded from the file represented by the string;
     * If this is an object, it must be a return value from static function {@link createTitleSetting}.
     * @param {string} context - A link to a PHP or HTML file that is loaded to be the context of the window.
     */
    constructor(title, context){
        this.#title = title;
        this.#context = context;
    }

    /**
     * Opens the current window.
     * @function
     * @param {function} done - What's going to happen after the sort is done?
     * @return {boolean} Returns true on success. Note that when the window is fading you can't...
     */
    open(done){
        if(this.#opened) return false;
        const windowThing = $('<div id="modal_back" style="display: none"></div>').append('<div id="window"></div>');
        let titleThing = $('<div id="title_box"></div>');
        if(typeof this.#title === 'object'){
            const titleObj = this.#title, text = titleObj["text"], iconlink = titleObj["iconlink"],
                color = titleObj["color"], backcolor = titleObj["backcolor"], hasclosebtn = titleObj["hasclosebtn"];

            titleThing.css("color", color).css('background-color', backcolor);
            if(iconlink !== null)
                titleThing.append('<img id="title_icon" src="/img/'+iconlink+'">');
            titleThing.append('<span id="title_text">'+text+'</span>');
            if(hasclosebtn)
                $('<a id="close_button" onclick="modelWindow.close();"></a>').appendTo(titleThing)
                    .append('<img src="/img/pages/icons/close.png">');
            finish(this);
        }
        else if(typeof this.#title === "string"){
            $.get('window/'+this.#title).done((content)=>{
                titleThing.append($(content));
                finish(this);
            });
        }
        else if (typeof this.#title === "undefined" || this.#title===false){
            titleThing = "";
            finish(this);
        }
        else console.error("Invalid type of parameter \"title\".", this.#title);

        function finish(self){
            $.get("window/"+self.#context).done((content)=>{
                const windowObj = windowThing.children('#window');
                if (titleThing!=="") windowObj.append(titleThing).append("<div id='title_separator'></div>");
                windowObj.append('<div id="content_box"></div>').children("#content_box").append($(content));
                $("body").append('<link href="/css/window.css" rel="stylesheet"/>').append(windowThing);
                windowThing.fadeIn('fast', ()=>{
                    self.#opened=true;
                    if(typeof done==="function") done();
                });
            })
        }
        return true;
    }

    /**
     * Closes the current window.
     * @function
     * @param {function} done - What's going to happen after the sort is done?
     * @return {boolean} Returns true on success. Note that when the window is fading you can't...
     */
    close(done){
        if (!this.#opened) return false;
        $('#modal_back').fadeOut("fast", ()=>{
            $('#modal_back').remove();
            this.#opened = false;
            if(typeof done==="function") done();
        });
        return true;
    }

    /**
     * Creates a title setting object for constructing a {@link ModelWindow} object.
     * @function
     * @param {string} text - The title's text.
     * @param {null|string} iconlink - A link to the icon of the title bar. Can be null.
     * @param {boolean} hasclosebtn - Whether the window itself has a close button.
     * @param {string} color - The text color of the title.
     * @param {string} backcolor - The background color of the title bar.
     * @return {Object} An object representing the window title setting.
     */
    static createTitleSetting(text, iconlink=null, hasclosebtn = true,
                              color="#1a1919", backcolor="#d0d0d0"){
        return {"text": text, "iconlink": iconlink, "hasclosebtn": hasclosebtn, "color": color, "backcolor": backcolor};
    }
}
