/**
 * @abstract
 * Represents a layout object that has the potential to be clicked.
 * @author gudako
 */
class Clickable extends LayoutObject{
    /**
     * @protected
     * The event handler when the element is clicked. Null if not set.
     * @type Function<event>|null
     */
    _onclick:(event:Event)=>void;

    /**
     * @constructor
     * @abstract
     * Construct a new clickable layout object.
     * @param size {string|[string,string]} The size of the element. For layout with fixed aspect ratio, only a width is needed;
     * Otherwise, both value must be specified.
     * @param loc {[string,string]} The location of the element, indicating the css property "top" and "left".
     * @param onclick {Function<Event>|null} The handler for the click. If this is set to null, it will behave as not clickable
     * and related animations (e.g. hover effect) will not display.
     * @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(size, loc=["0", "0"], onclick:(event:Event)=>void=null, parent="body"){
        super(size, loc, parent);
        this._onclick = onclick;
    }

    /** @inheritdoc */
    aspectRatio(){};

    /** @inheritdoc */
    _fontSizeMultiplier(){};

    /** @inheritdoc */
    _elementFile(){};

    /** @inheritdoc */
    _postImport(element){
        super._postImport();
        this._addInterfaceAttr("clickable");
    };

    /** @inheritdoc */
    _postAppear(element) {
        super._postAppear(element);
        if(this._onclick!==null) element.click(this._onclick);
    }

    /** @inheritdoc */
    _postDestruct(element) {
        super._postDestruct(element);
        element.off("click");
    }
}
