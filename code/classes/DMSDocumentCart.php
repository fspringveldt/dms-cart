<?php
/**
 * Class DMSDocumentCart represents the shopping cart.
 *
 */
class DMSDocumentCart extends ViewableData
{
    /**
     * A handle to the classes' {@link DMSCartBackendInterface}
     *
     * @var DMSCartBackendInterface
     */
    protected $backend;

    public function __construct($backend = null)
    {
        parent::__construct();
        $this->backend = ($backend) ?: DMSSessionBackend::singleton(); // Default to DMSSessionBackend if not provided
    }

    /**
     * Returns all the cart items as an array
     *
     * @return ArrayList
     */
    public function getItems()
    {
        return ArrayList::create($this->backend->getItems());
    }

    /**
     * Add an {@link DMSRequestItem} object into the cart.
     *
     * @param DMSRequestItem $item
     *
     * @return DMSDocumentCart
     */
    public function addItem(DMSRequestItem $item)
    {
        $this->backend->addItem($item);

        return $this;
    }

    /**
     * Get a {@link DMSRequestItem} object from the cart.
     *
     * @param int $itemID The ID of the item
     *
     * @return DMSRequestItem|boolean
     */
    public function getItem($itemID)
    {
        return $this->backend->getItem($itemID);
    }

    /**
     * Removes a {@link DMSRequestItem} from the cart by it's id
     *
     * @param DMSRequestItem $item
     *
     * @return DMSDocumentCart
     */
    public function removeItem(DMSRequestItem $item)
    {
        $this->backend->removeItem($item);

        return $this;
    }

    /**
     * Removes a {@link DMSRequestItem} from the cart by it's id
     *
     * @param int $itemID
     *
     * @return DMSDocumentCart
     */
    public function removeItemByID($itemID)
    {
        $this->backend->removeItemByID($itemID);

        return $this;
    }

    /**
     * Adjusts (increments, decrements or amends) the quantity of an {@link DMSRequestItem}.'
     * A positive $quantity increments the total, whereas a negative value decrements the total. A cart item
     * is removed completely if it's value reaches <= 0.
     *
     * @param int $itemID
     * @param int $quantity
     *
     * @return DMSDocumentCart
     */
    public function updateItemQuantity($itemID, $quantity)
    {
        if ($item = $this->getItem($itemID)) {
            $currentQuantity = $item->getQuantity();
            $newQuantity = $currentQuantity + $quantity;
            if ($newQuantity <= 0) {
                $this->removeItemByID($itemID);
            } else {
                $item->setQuantity($newQuantity);
                $this->addItem($item);
            }
        }

        return $this;
    }

    /**
     * Completely empties a cart
     *
     * @return DMSDocumentCart
     */
    public function emptyCart()
    {
        $this->backend->emptyCart();

        return $this;
    }

    /**
     * Checks if a cart is empty.
     * Returns true if cart is empty, false otherwise.
     *
     * @return boolean
     */
    public function isCartEmpty()
    {
        $items = $this->getItems();

        return !$items->exists();
    }

    /**
     * Set the backURL to be a Session variable for the current Document Cart
     *
     * @param string $backURL
     *
     * @return DMSDocumentCart
     */
    public function setBackUrl($backURL)
    {
        $this->backend->setBackUrl($backURL);

        return $this;
    }

    /**
     * Returns the backURL for the current Document Cart
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->backend->getBackUrl();
    }

    /**
     * Sets the recipients info as an array (e.g. array('Name'=>'Joe','Surname'=>'Soap'))
     *
     * @param array $receiverInfo
     *
     * @return DMSDocumentCart
     */
    public function setReceiverInfo($receiverInfo)
    {
        $this->backend->setReceiverInfo($receiverInfo);

        return $this;
    }

    /**
     * Retrieves the recipients info as an array (e.g. array('Name'=>'Joe','Surname'=>'Soap'))
     *
     * @return array
     */
    public function getReceiverInfo()
    {
        return $this->backend->getReceiverInfo();
    }

    /**
     * Returns the recipients in a Viewable format
     *
     * @return ArrayData|bool
     */
    public function getReceiverInfoNice()
    {
        return (is_array($this->getReceiverInfo())) ? ArrayData::create($this->getReceiverInfo()) : false;
    }

    /**
     * Gets the backend handler
     *
     * @return DMSSessionBackend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Checks if an item exists within a cart. Returns true (if exists) or false.
     *
     * @param int $itemID
     *
     * @return bool
     */
    public function isInCart($itemID)
    {
        return (bool) $this->getItem($itemID);
    }

    /**
     * Persists a cart submission to the database
     *
     * @param Form $form
     *
     * @return int
     */
    public function saveSubmission(Form $form)
    {
        $submission = DMSDocumentCartSubmission::create();
        $form->saveInto($submission);
        $return = $submission->write();
        $this->getItems()->each(function ($row) use ($submission) {
            $values = array(
                'Quantity' => $row->getQuantity(),
                'DocumentID' => $row->getDocument()->ID,
            );
            $submissionItem = DMSDocumentCartSubmissionItem::create($values);
            $submission->Items()->add($submissionItem);

            $row->getDocument()->incrementPrintRequest();
        });

        return $return;
    }
}
