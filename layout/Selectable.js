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
     * @type Array<Selectable>|false|null
     */
    _selectionGroup;


}