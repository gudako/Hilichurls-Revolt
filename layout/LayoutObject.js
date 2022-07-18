/**
 * @abstract
 * Represents a layout object, whether it's an in-game window, or item description box.
 * @author gudako
 */
class LayoutObject{
    // ----------------- FIELDS DEFINITION ----------------- //
    // PRIVATE FIELDS
    /**
     * @private
     * A JQuery object containing the element of the layout.
     * @type jQuery
     */
    #element;

    /**
     * @private
     * Indicates whether the layout object is visible.
     * @type boolean
     */
    #visible = false;

    /**
     * @private
     * Indicates the size [height, width] of the element.
     * @type [string,string]
     */
    #size;

    /**
     * @private
     * Indicates the parent of the element. It can be a string css selector, or another {@link LayoutObject}.
     * @type LayoutObject|string
     */
    #parent;

    /**
     * @private
     * Indicates the children of the element.
     * @type Array<LayoutObject>
     */
    #children = [];

    // ---------------------- CONSTRUCTOR ---------------------- //
    /**
     @constructor
     @abstract
     Construct a new layout object.
     @param size {string|[string,string]} The size of the element. For layout with fixed aspect ratio, only a width is needed;
     Otherwise, both value must be specified.
     @param loc {[string,string]} The location of the element, indicating the css property "top" and "left".
     @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(size, loc=["0", "0"], parent="body"){
        if(this.aspectRatio()!==0){
            const width = Array.isArray(size)?size[1]:size;
            this.#size =[LayoutObject.#calcAttr(width,val=>val/this.aspectRatio()),width];
        }
        else this.#size = size;
        this.size(this.#size);
        this.#parent = parent;
        if(parent instanceof LayoutObject) parent.#children.push(this);
        
        LayoutObject.#getAvailable4dAttrs().forEach(elem=>this.#init4DirectionVal(elem));
        this.#set4DirectionVal("",[loc[0],"initial","initial",loc[1]]);

        let fileContent;
        const uri = location.hostname+this._elementFile();
        $.get({
            async: false,
            url: uri,
            error: (jqXHR,textStatus,errorThrown)=>{throw new Error("Failed to load file \""+uri+"\": "+errorThrown);},
            success: (data, textStatus, jqXHR)=>{fileContent = data;}
        });
        this.#element = fileContent.parseHTML(); //todo things
        this._postImport(this.#element);
    };

    // ----------------- PRIVATE METHODS DOWN ----------------- //

    /**
     @function
     @static
     @private
     Convert a string of a 4-dimensional css value to an array with these 4 values.
     @param {string} value It can be a css 4-dimension attr value (a string).
     A 4-dimension attr is an attr that can be split into values of 4 directions, like "margin" can be split into
     "margin-top", "margin-right", "margin-bottom" and "margin-left".
     @return {[string,string,string,string]} An array with four values, respectively representing "-top", "-right", "-bottom", "-left".
     */
    static #parse4dimensionVal(value){
        let unitstrs = value.replace("  "," ").trim().split(" ");
        const lens = unitstrs.length;
        if(lens===0||lens===3||lens>4)
            throw new Error("Invalid css-unit-string count for 4-dimension parsing.");

        unitstrs = unitstrs.map(elem=>elem.trim().toLowerCase());
        let res;
        switch (unitstrs.length){
            case 1:
                res=[unitstrs[0],unitstrs[0],unitstrs[0],unitstrs[0]];
                break;
            case 2:
                res=[unitstrs[0],unitstrs[1],unitstrs[0],unitstrs[1]];
                break;
            case 4:
                res=[unitstrs[0],unitstrs[1],unitstrs[2],unitstrs[3]];
        }
        return res;
    }

    /**
     @function
     @static
     @private
     Do calculation on an attribute string with unit.
     @param val {string} The value to be calculated.
     @param alg {Function<number,number>} The function to calculate the value.
     @return {string} The calculation result.
     */
    static #calcAttr(val, alg:(val:number)=>number){
        val = val.trim();
        let i;
        for(i=0; i<=val.length-1; i++) if(val[i].toLowerCase()!==val[i].toUpperCase()) break;
        const numpart = val.substring(0,i);
        const unitpart = val.substring(i);
        return alg(parseInt(numpart))+unitpart;
    }

    /**
     @function
     @static
     @private
     @return {Array<string>}
     */
    static #getAvailable4dAttrs(){
        return ["","margin"];
    }

    /**
     @function
     @private
     Initialize the 4-dimensional attr values that should be stored in the object.
     @param {string} attr The 4-dimensional css attr to give a memory to.
     */
    #init4DirectionVal(attr){
        this[attr+"-top"] = "0";
        this[attr+"-left"] = "0";
        if(attr===""){
            this[attr+"-right"] = "initial";
            this[attr+"-bottom"] = "initial";
        }
        else{
            this[attr+"-right"] = "0";
            this[attr+"-bottom"] = "0";
        }
    }

    /**
     @function
     @private
     Set a 4-dimensional css attr value and store it in memory.
     @param attr {string} The css attr name.
     @param value {string} The value to be set.
     */
    #set4DirectionVal(attr, value){
        const unitstrs = LayoutObject.#parse4dimensionVal(value);
        const rep = {};
        rep[attr+"-top"] = unitstrs[0];
        rep[attr+"-right"] = unitstrs[1];
        rep[attr+"-bottom"] = unitstrs[2];
        rep[attr+"-left"] = unitstrs[3];
        const outside = this;
        for (const [key, value] of Object.entries(rep)) {
            if(value==="hold") continue;
            this.#element.css(key[0]==="-"?key.substring(1):key, value);
            outside[key] = value;
        }
    }

    // ---------------- PROTECTED METHODS DOWN ---------------- //

    // ABSTRACT METHODS
    /**
     * @function
     * @abstract
     * @protected
     * Get the multiplier that multiplies with the width to get the percentage of font size.
     * @return {number} Returns the multiplier.
     */
    _fontSizeMultiplier(){};

    /**
     * @function
     * @abstract
     * @protected
     * Get the HTML or PHP file path to import as the element.
     * @return {string} Returns the file path relative to the root, with a backslash at the beginning.
     */
    _elementFile(){};

    /**
     * @function
     * @abstract
     * @protected
     * The actions to be done after the element file import. The element remains invisible when this function is called.
     * Typically, this is used to alter the element's appearance.
     * @param element {jQuery} The element of the layout object.
     */
    _postImport(element){};

    /**
     * @function
     * @abstract
     * @protected
     * The actions to be done after the element is fully faded in and shown. Typically, this is to add some event handler.
     * @param element {jQuery} The element of the layout object.
     */
    _postAppear(element){};

    /**
     * @function
     * @abstract
     * @protected
     * The actions to be done after the element is fully faded out and removed. Typically, this is to remove some event handler.
     * @param element {jQuery} The element of the layout object.
     */
    _postDestruct(element){};

    // VIRTUAL METHODS
    /**
     * @function virtual
     * @protected
     * Actions after the fade in animation, in attempt to show the layout object.
     */
    _show(){
        if(!this.#element.length||!this.#element.is(":visible"))
            throw new Error("Check your \"action\" function in \"appear\". It must make the element visible.");
        this.#visible = true;
    }

    /**
     * @function virtual
     * @protected
     * Actions after the fade out animation, in attempt to remove the layout object.
     */
    _remove(){
        if(this.#element.length)
            throw new Error("Check your \"action\" function in \"disappear\". It must make the element invisible.");
        this.#visible = false;
        this.#children.forEach(val=>val.disappear());
    }

    // FINAL METHODS
    /**
     * @function final
     * @protected
     * Adds a value to the HTML attribute \"data-interface\" of the outer element. If there is no such attribute, it will be created.
     * @param {string} key - The value to add.
     */
    _addInterfaceAttr(key){
        const eAttr = this.#element.attr("data-interface");
        if(typeof eAttr==="undefined"||eAttr===false){
            this.#element.attr("data-interface",key);
        }
        else this.#element.attr("data-interface",eAttr+" "+key);
    }

    // ------------------ PUBLIC METHODS DOWN ------------------ //

    // ABSTRACT METHODS
    /**
     * @function
     * @abstract
     * Get the aspect ratio of the element. Calculated as width/height.
     * @return {number} Returns the aspect ratio. For a layout object having non-fixed value, this is 0.
     */
    aspectRatio();

    // FINAL METHODS
    /**
     * @function final
     * Get whether the layout object is currently visible.
     * @return {boolean} Returns true when the layout is visible.
     */
    isVisible(){
        return this.#visible;
    }

    /**
     * @function final
     * Gets or sets the size of the element.
     * @param value {null|[null|string,null|string]} A tuple [height, width] to set the size. If height or width is
     * null, it remains the original. If the aspect ratio is fixed, and both width and height are to set, only the width is counted;
     * If this is null, the method gets the size.
     * @return {this|[string,string]} Returns a readonly tuple [height, width] when get; Otherwise, returns this.
     */
    size(value=null){//todo
        if(value===null) return [this.#size[0],this.#size[1]].freeze();
        if(this.aspectRatio()===0){
            if(value[0]!==null) {
                this.#size[0] = value[0];
                this.#element.css("height", value[0]);
            }
            if(value[1]!==null) {
                this.#size[1] = value[1];
                this.#element.css("width", value[1]);
            }
        }
        else{
            const outside = this;
            if(value[1]!==null){
                this.#size[1] = value[1];
                this.#size[0] = LayoutObject.#calcAttr(value[1],val=>val/outside.aspectRatio());
                this.#element.css("width", value[1]);
            }
            else if(value[0]!==null){
                this.#size[0] = value[0];
                this.#size[1] =  LayoutObject.#calcAttr(value[0],val=>val*outside.aspectRatio());
                this.#element.css("width", value[1]);
            }
        }
        return this;
    }

    /**
     * @function final
     * Get or set an attribute of the element.
     * @param key {string} The css property to set or get.
     * If this is empty, it targets the [top, right, bottom, left] css property;
     * If this is empty or "margin", getting this value returns a string that may contain relative units.
     * Otherwise, the get&set functions the same as jQuery.css
     * @param value {any} If this is set to null, the css property is got; Otherwise, the property is written as this value.
     * @return {this|any} Returns the result when it's to get an attribute; Otherwise, return this.
     */
    css(key="", value=null){
        key = key.trim().toLowerCase();
        let subkey;
        if(["top","right","bottom","left"].includes(key)){
            subkey = key;
            key = "";
        }
        else if(key.endsWith("-top")){
            subkey = "top";
            key = key.substring(0,key.length-1-4);
        }
        else if(key.endsWith("-right")){
            subkey = "right";
            key = key.substring(0,key.length-1-6);
        }
        else if(key.endsWith("-bottom")){
            subkey = "bottom";
            key = key.substring(0,key.length-1-7);
        }
        else if(key.endsWith("-left")){
            subkey = "left";
            key = key.substring(0,key.length-1-5);
        }
        else subkey=null;

        if(["padding","border","height","width"].includes(key))
            throw new Error("Unable to set css \""+key+"\": Access denied.");

        const opt = LayoutObject.#getAvailable4dAttrs().includes(key);
        let localset = opt&&subkey!==null?key+"-"+subkey:null;
        if(localset!==null){
            if(value===null) return this[localset];
            else {
                this[localset] = value;
                if(localset[0]==="-")localset=localset.substring(1);
                this.#element.css(localset, value);
                return this;
            }
        }
        else if(opt){
            if(value===null) this.#set4DirectionVal(key, value);
            else return [this[key+"-top"],this[key+"-right"],this[key+"-bottom"],this[key+"-left"]].join(" ");
            return this;
        }
        else{
            if(subkey!==null) key += "-" + subkey;
            if(value===null) return this.#element.css(key);
            this.#element.css(key, value);
            return this;
        }
    }

    /**
     * @function final
     * Add the element to the document with or without fading effects.
     * @param action {Function<jQuery, Object, Function, void>|false}
     * A function to fade the invisible layout to a visible state;
     * It's parameters:
     * (1) element: The jQuery object passed in.
     * (2) prop: An object with field "top", "left", "width" and "height".
     * (3) callback: The callback function when the fade ends.
     * Notice that the object is right in place with its size and position before the function call.
     * If this parameter is false, the element will directly show without animation.
     * @return {boolean} False only if the element already visible.
     */
    appear(action:(element:jQuery,prop:Object,callback:Function)=>void=false){
        if(this.#visible) return false;
        let append2;
        if(this.#parent instanceof LayoutObject) append2 = this.#parent.#element;
        else if(typeof this.#parent === "string"){
            append2 = this.#parent;
            if($(append2).length===0) throw new Error("Cannot find an object matching the parent css selector.");
            else if($(append2).length>1) console.warn("More than 1 parent element is matched.");
        }
        else throw new Error("Invalid type of \"#parent\" field.");

        this.#element.hide().appendTo(append2);
        if(typeof action === "function") {
            action(this.#element,
                {
                    loc: this.css(),
                    size: this.#size
                },()=>{ this._show();this._postAppear(this.#element)});
        }
        else if(action === false){
            this.#element.show();
            this._show();
            this._postAppear(this.#element);
        }
        else throw new Error("Invalid type of parameter \"action\".");
        return true;
    }

    /**
     * @function final
     * Remove the element from document with or without fading effects.
     * @param action {Function<jQuery, Function, void>|false}
     * A function to fade the visible layout to an invisible state;
     * It's parameters:
     * (1) element: The jQuery object passed in.
     * (2) callback: The callback function when the fade ends.
     * If this parameter is false, the element will directly disappear without animation.
     * @return {boolean} False only if the element already invisible.
     */
    disappear(action:(element:jQuery,callback:Function)=>void=false){
        if(!this.#visible) return false;
        if(typeof action === "function") action(this.#element, ()=>{this._remove();
            this._postDestruct(this.#element);});
        else if(action === false){
            this.#element.remove();
            this._remove();
            this._postDestruct(this.#element);
        }
        else throw new Error("Invalid type of parameter \"action\".");
        return false;
    }
}
