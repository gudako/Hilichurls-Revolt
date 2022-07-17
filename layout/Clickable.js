/**
 * @abstract
 * Represents a layout object that has the potential to be clicked.
 * @author gudako
 */
class Clickable extends LayoutObject{

    /**
     @constructor
     @abstract
     @param {false|Function<Event>} onClick The action to be performed on the click.
     */
    constructor(onClick=false) {
        super();

    }

    _show() {
        super._show();

    }

    _remove() {
        super._remove();
        this._element.off();
    }
}