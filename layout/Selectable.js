/**
 * @abstract
 * Represents a layout object that has the potential to be clicked and selected.
 * @author gudako
 */
class Selectable extends Clickable{
    /**
     * @protected
     * If this acts as a radiobox, this is an array of the selection group that contains this object;
     * If this acts as a checkbox, this is false; If this is unselectable, this is null;
     * @type Array|false|null
     */
    _selgroup;

    /**
     * @constructor
     * @abstract
     * Construct a new clickable layout object.
     * @param size {string|[0,string]} The width of the element. Can also be a tuple, but the first part indicating the height is ignored.
     * @param loc {[string,string]} The location of the element, indicating the css property "top" and "left".
     * @param onclick {Function<Event>|null} The handler for the click. If this is set to null, it will behave as not clickable
     * and related animations (e.g. hover effect) will not display.
     * @param selgroup {null|boolean|Selectable} If this is null, it will behave as not selectable;
     * If this is a bool, it's a checkbox and the bool indicates It's initially selected or not;
     * If this is a {@link Selectable}, it's a radiobox, and it joins the selection-group of that {@link Selectable}.
     * This {@link Selectable} can be self: In this case, a new selection-group is created.
     * @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(size, loc=["0", "0"], onclick=null, selgroup=null, parent="body"){
        super(size, loc, onclick, parent);
        if(this === selgroup){
            this._selgroup = [-1, this];
        }
        else if(selgroup instanceof Selectable){
            this._selgroup = selgroup._selgroup;
            this._selgroup.push(this);
        }
        else this._selgroup = selgroup;
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
        if(this._selgroup!==null) element.attr("data-selected")
    };

    /** @inheritdoc */
    _postAppear(element) {
        super._postAppear(element);
    }

    /** @inheritdoc */
    _postDestruct(element) {
        super._postDestruct(element);
    }

    _refreshSelectState
}