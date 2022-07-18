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
     @param size {string|[0,string]} The width of the element. Can also be a tuple, but the first part indicating the height is ignored.
     @param loc {[string,string]} The location of the element, indicating the css property "top" and "left".
     @param onClick {Function<Event>|null} The handler for the click. If this is set to null, it will behave as not clickable
     and related animations (e.g. hover effect) will not display.
     @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(size, loc=["0", "0"], onClick:(event:Event)=>void=null, parent="body"){
        super(size, loc, parent);
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