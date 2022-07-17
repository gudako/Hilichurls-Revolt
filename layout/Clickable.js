/**
 * @abstract
 * Represents a layout object that has the potential to be clicked.
 * @author gudako
 */
class Clickable extends LayoutObject{

    _onClick;

    constructor(width, position, parent="body", onClick:(event:Event)=>void=null){
        super(width, position, parent);
        this._onClick = onClick;
    }

    aspectRatio(){};
    _fontSizeMultiplier(){};
    _elementFile(){};
    _postImport(element){
        if(this._onClick!==null){
            element.children("style-click").replaceWith("style"); //todo test
        }
    };

    _postAppear(element) {
        super._postAppear(element);
        if(this._onClick!==null) element.click(this._onClick);
    }

    _postDestruct(element) {
        super._postDestruct(element);
        element.off("click");
    }
}