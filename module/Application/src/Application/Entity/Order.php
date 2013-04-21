<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="Orders")
 * @ORM\Entity
 */
class Order {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $customer;

    /** @ORM\Column(type="datetime") */
    protected $created_date;

    /**
    * @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order", orphanRemoval=true) 
    * @ORM\JoinColumn(name="order_id", referencedColumnName="id")
    */
    protected $items;


    public function getId() {
        return $this->id;
    }

    public function getItems() {
        return $this->items;
    }

    public function setCustomer($customer) {
        $this->customer = $customer;
    }

    public function getCustomer() {
        return $this->customer;
    }

    public function setTotal($total) {
        $this->total = $total;
    }

    public function getTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            $total+=$item->getPrice() * $item->getQuantity();
        }
        return $total;
    }

    public function setCreatedDate()
    {
        $this->created_date = new \DateTime();
    }

    public function createdDate()
    {
        return $this->created_date;
    }
}