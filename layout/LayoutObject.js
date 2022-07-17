/**
 * @abstract
 * Represents a layout object, whether it's an in-game window, or item description box.
 * @author gudako
 */
class LayoutObject{
    // ----------------- FIELDS DEFINITION ----------------- //
    /**
     * @protected
     * A JQuery object containing the element of the layout.
     * @type jQuery
     */
    _element;

    /**
     * @protected
     * Indicates whether the layout object is visible.
     * @type boolean
     */
    _visible = false;

    /**
     * @protected
     * Indicates the width of the element.
     * @type number
     */
    _width;

    /**
     * @protected
     * Indicates the position of the element.
     * @type [number,number]
     */
    _position;

    /**
     * @protected
     * Indicates the parent of the element. It can be a string css selector, or another {@link LayoutObject}.
     * @type LayoutObject|string
     */
    _parent;

    /**
     * @protected
     * Indicates the children of the element.
     * @type Array<LayoutObject>
     */
    _children = [];

    // ---------------------- CONSTRUCTOR ---------------------- //
    /**
     @constructor
     @abstract
     @param width {number} The width of the element. If the element is not a resizable, the height is automatic with the aspect ratio.
     @param position {[number,number]} The position of the element, indicating the css property "top" and "left".
     The element is always "position: absolute".
     @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(width, position, parent="body"){
        this._width = width;
        this._position = position;
        this._parent = parent;
        if(parent instanceof LayoutObject){
            parent._children.push(this);
        }
    };

    // ------------------ PUBLIC METHODS DOWN ------------------ //

    // ABSTRACT METHODS
    /**
     * @function
     * @abstract
     * Get the aspect ratio of the element.
     * @return {number} Returns the aspect ratio.
     */
    aspectRatio(){};

    // FINAL METHODS
    /**
     * @function final
     * Get whether the layout object is currently visible.
     * @return {boolean} Returns true when the layout is visible.
     */
    isVisible(){
        return this._visible;
    }

    /**
     * @function final
     * Get the size of the element.
     * @return {[number,number]} Returns a tuple [height, width].
     */
    getSize(){
        return [this._width/this.aspectRatio(), this._width];
    }

    /**
     * @function final
     * Get the position of the element.
     * @return {[number,number]} Returns a tuple [top, left].
     */
    getPosition(){
        return this._position;
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

    // VIRTUAL METHODS
    /**
     * @function virtual
     * @protected
     * Actions after the fade in animation, in attempt to show the layout object.
     */
    _show(){
        if(!this._element.length||!this._element.is(":visible"))
            throw new Error("Check your \"action\" function in \"appear\". It must make the element visible.");
        this._visible = true;
    }

    /**
     * @function virtual
     * @protected
     * Actions after the fade out animation, in attempt to remove the layout object.
     */
    _remove(){
        if(this._element.length)
            throw new Error("Check your \"action\" function in \"disappear\". It must make the element invisible.");
        this._visible = false;
    }

    // FINAL METHODS
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
    appear(action=false){
        if(this._visible) return false;

        let append2;
        if(this._parent instanceof LayoutObject) append2 = this._parent._element;
        else if(typeof this._parent === "string"){
            append2 = this._parent;
            if($(append2).length===0) throw new Error("Cannot find an object matching the parent css selector.");
            else if($(append2).length>1) console.warn("More than 1 parent element is matched.");
        }
        else throw new Error("Invalid type of \"_parent\" field.");

        this._element.hide().appendTo(append2).css("position", "absolute")
            .css("top", this._position[0]).css("left", this._position[1])
            .css("height", this._width/this.aspectRatio()).css("width",this._width);

        if(typeof action === "function") {
            action(this._element,
                {
                    top: this._position[0],
                    left: this._position[1],
                    width: this._width,
                    height: 1.0 * this._width / this.aspectRatio()
                }, this._show);
        }
        else if(action === false){
            this._element.show();
            this._show();
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
    disappear(action=false){
        if(!this._visible) return false;
        if(typeof action === "function") action(this._element, this._remove);
        else if(action === false){
            this._element.remove();
            this._remove();
        }
        else throw new Error("Invalid type of parameter \"action\".");
        return false;
    }
}