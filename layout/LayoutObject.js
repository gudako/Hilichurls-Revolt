/**
 * @abstract
 * Represents a layout object, whether it's an in-game window, or item description box.
 * @author gudako
 */
class LayoutObject{
    /**
     * @protected
     * A JQuery object containing the element of the layout.
     * @type jQuery
     */
    _element = false;

    /**
     * @protected
     * Indicates whether the layout object is visible.
     * @type boolean
     */
    _visible = false;

    /**
     @constructor
     @abstract
     */
    constructor(){

    };

    /**
     * @function
     * Get whether the layout object is currently visible.
     * @return {boolean} Returns true when the layout is visible.
     */
    isVisible(){
        return this._visible;
    }

    /**
     * @function
     * Add the element to the document with or without fading effects.
     * @param append2 {string} The jQuery css selector indicating which element should the element append to.
     * @param action {Function<jQuery, Function<>, void>|false}
     * A function to fade the invisible layout to a visible state; Then, it must call the callback function (second param) and return true on success.
     * If this parameter is false, the element will directly show without animation.
     * @return {boolean} False only if the element already visible.
     */
    appear(append2 = "body", action=false){
        if(this._visible) return false;
        this._element.hide().appendTo(append2);
        if(typeof action === "function") action(this._element, this.#show);
        else if(action === false){
            this._element.show();
            this.#show();
        }
        else throw new Error("Invalid type of parameter \"action\".");
        return true;
    }

    /**
     * @function
     * Remove the element from document with or without fading effects.
     * @param action {Function<jQuery, Function<>, void>|false}
     * A function to fade the visible layout to an invisible state; Then, it must call the callback function (second param).
     * If this parameter is false, the element will directly hide without animation.
     * @return {boolean} False only if the element already invisible.
     */
    disappear(action=false){
        if(!this._visible) return false;
        if(typeof action === "function") action(this._element, this.#hide);
        else if(action === false){
            this._element.remove();
            this.#hide();
        }
        else throw new Error("Invalid type of parameter \"action\".");
        return false;
    }

    #show(){
        if(!this._element.length||!this._element.is(":visible"))
            throw new Error("Check your \"action\" function in \"appear\". It must make the element visible.");
        this._visible = true;

    }

    #hide(){
        if(this._element.length)
            throw new Error("Check your \"action\" function in \"disappear\". It must make the element invisible.");
        this._visible = false;

    }
}