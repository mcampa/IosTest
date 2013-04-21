<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="OrderItems")
 * @ORM\Entity
 */
class OrderItem {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $product;

    /** @ORM\Column(type="integer") */
    protected $quantity;

    /** @ORM\Column(type="decimal", precision=10, scale=2) */
    protected $price;

    /**
    * @ORM\ManyToOne(targetEntity="Order")
    * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
    */
    protected $order;


    public function __construct($itemData = null) {
        if ($itemData) {            
            $this->setProduct($itemData->product);
            $this->setQuantity($itemData->quantity);
            $this->setPrice($itemData->price);
        }
    }

    public function setData($itemData) {
        $this->setProduct($itemData->product);
        $this->setQuantity($itemData->quantity);
        $this->setPrice($itemData->price);
    }

    public function getId() {
        return $this->id;
    }

    public function getOrder() {
        return $this->order;
    }

    public function setOrder($order) {
        $this->order = $order;
    }

    public function setProduct($product) {
        $this->product = $product;
    }

    public function getProduct() {
        return $this->product;
    }

    public function setQuantity($quantity) {
        $this->quantity = $quantity;
    }

    public function getQuantity() {
        return $this->quantity;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function getPrice() {
        return $this->price;
    }
}