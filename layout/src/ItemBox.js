/**
 * Represents an item box that can be selectable.
 * @author gudako
 */
class ItemBox extends LayoutObject{

    #clickable;
    constructor(clickable=false) {
        super();
    }

    isClickable() {
        return this.#clickable;
    }

    _setClickHandler(handler) {
    }
}