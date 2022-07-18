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
    _onClick:(event:Event)=>void;

    /**
     @constructor
     @abstract
     Construct a new clickable layout object.
     @param positioning {string} The positioning type (relative; absolute;) of the element.
     @param size {string|number|[0,string|number]} The size of the element. Can also be a tuple, but the first part indicating the height is ignored.
     @param position {[string|number,string|number]} The position of the element, indicating the css property "top" and "left".
     The element is always "position: absolute".
     @param onClick {Function<Event>|null} The handler for the click. If this is set to null, it will behave as not clickable
     and related animations (e.g. hover effect) will not display.
     @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(size, position, positioning="absolute", onClick:(event:Event)=>void=null, parent="body"){
        super(size, position, positioning, parent);
        this._onClick = onClick;
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
        if(this._onClick!==null) element.click(this._onClick);
    }

    /** @inheritdoc */
    _postDestruct(element) {
        super._postDestruct(element);
        element.off("click");
    }
}