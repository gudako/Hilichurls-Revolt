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
     * @type {Array<Selectable>|false|null}
     */
    _selgroup;

    /**
     * @protected
     * If this acts as a radiobox, this is the index at which element is selected (-1 for none);
     * Otherwise, this is false;
     * @type {int|false}
     */
    _selindex=false;

    /**
     * @constructor
     * @abstract
     * Construct a new clickable layout object.
     * @param size {string|[0,string]} The width of the element. Can also be a tuple, but the first part indicating the height is ignored.
     * @param loc {[string,string]} The location of the element, indicating the css property "top" and "left".
     * @param onclick {Function<Event>|null} The handler for the click. If this is set to null, it will behave as not clickable
     * and related animations (e.g. hover effect) will not display.
     * @param selgroupof {null|boolean|Selectable} If this is null, it will behave as not selectable;
     * If this is a bool, it's a checkbox and the bool indicates It's initially selected or not;
     * If this is a {@link Selectable}, it's a radiobox, and it joins the selection-group of that {@link Selectable}.
     * This {@link Selectable} can be self: In this case, a new selection-group is created.
     * @param parent {LayoutObject|string} The parent of the layout object. It can be a string css selector, or another {@link LayoutObject}.
     */
    constructor(size, loc=["0", "0"], onclick=null, selgroupof=null, parent="body"){
        super(size, loc, onclick, parent);
        if(this === selgroupof){
            this._selgroup = [this];
            this._selindex = -1;
        }
        else if(selgroupof instanceof Selectable){
            this._selgroup = selgroupof._selgroup;
            this._selgroup.push(this);
            this._selindex = this._selgroup[0]._selindex;
        }
        else this._selgroup = selgroupof;
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
        if(this._selgroup!==null) element.attr("data-selected",this._selgroup===true?"true":"false");
    };

    /** @inheritdoc */
    _postAppear(element) {
        super._postAppear(element);
    }

    /** @inheritdoc */
    _postDestruct(element) {
        super._postDestruct(element);
    }

    // ------------- PRIVATE METHODS DOWN ------------- //

    /**
     * @function
     * @private
     * Only available to radiobox selectables. Refresh the state.
     * @param referTo {int} The index at where the element to refer to.
     */
    #refreshSelectState(referTo){
        this._selindex = this._selgroup[referTo]._selindex;
        for(let i=0;i<=this._selgroup.length-1;i++){
            const sel = this._selindex === i;
            this._selgroup[i]._element.attr("data-selected",sel?"true":"false");
        }
    }

    // -------------- PUBLIC METHODS DOWN -------------- //

    /**
     * @function
     * Get whether this object is selectable.
     * @return {boolean} True if selectable.
     */
    isSelectable(){
        return this._selgroup!==null;
    }

    /**
     * @function
     * Get whether this object is a radiobox selectable.
     * @return {boolean} True if the object is a radiobox selectable.
     */
    isRadiobox(){
        return this._selindex!==false;
    }

    /**
     * @function
     * Get the selection group.
     * @return {Array<Selectable>|false} Returns the readonly selection group; Returns false if this is not a radiobox selectable.
     */
    getGroup(){
        if(!this.isRadiobox())return false;
        return [...this._selgroup].freeze();
    }

    /**
     * @function
     * Get the selected index in the selection group.
     * @return {int|false} Returns the index; Returns false if this is not a radiobox selectable.
     */
    getSelectedIndex(){
        return this._selindex??false;
    }

    /**
     * @function
     * Toggle this selectable. This will not deselect a radiobox selectable.
     */
    toggle(){
        const selected=this.isSelected();
        if(this.isRadiobox()&&selected)return;
        this.select(!selected);
    }

    /**
     * @function
     * Select or deselect this selectable.
     * @param sel {boolean} True to select; False to deselect.
     */
    select(sel=true){
        if(this.isRadiobox()){
            const thisindex =this._selgroup.indexOf(this);
            const equindex = this._selindex === thisindex;
            if((equindex&&sel)||(!equindex&&!sel)) return;
            this._selindex = sel? thisindex:-1;
            this._selgroup.forEach(elem=>elem.#refreshSelectState(this._selindex));
        }
        else{
            this._selgroup = sel;
            this._element.attr("data-selected",sel?"true":"false");
        }
    }

    /**
     * @function
     * Returns whether the selectable is selected.
     * @return {boolean|null} Returns the selection state; Null if the element is not selectable.
     */
    isSelected(){
        if(!this.isSelectable())return null;
        return this._element.attr("data-selected")==="true";
    }
}