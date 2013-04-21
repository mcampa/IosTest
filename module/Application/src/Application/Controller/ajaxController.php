<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Controller\EntityUsingController;
use Zend\View\Model\JsonModel;

use Application\Entity\OrderItem;
use Application\Entity\Order;

class ajaxController extends EntityUsingController
{

	public function indexAction()
	{
		$em = $this->getEntityManager();

		$orders = $em->getRepository('Application\Entity\Order')->findBy(array(), array('name' => 'ASC'));

		return new JsonModel(array('orders' => $orders,));
	}

	public function orderAction() {        
			
		$em = $this->getEntityManager();

		$items = array();
		
		$order_id = $this->params('id');
		$order = $em->getRepository('Application\Entity\Order')->find($order_id);

		foreach ($order->getItems() as $item) {

			$items[] = array(
				'id'  	   => $item->getId(),
				'product'  => $item->getProduct(),
				'quantity' => $item->getQuantity(),
				'price'    => $item->getPrice(),
			);
		}

		$response = array('items' => $items);


		return new JsonModel($response);
	}


	public function saveItemAction() {

		$response = array();

		$request = $this->getRequest();
		if ($request->isPost()) {
			
			$em = $this->getEntityManager();

			$order_id = $this->params('id');
			$order = $em->getRepository('Application\Entity\Order')->find($order_id);

			if ($order) {

				$data = $request->getPost();

				$itemData = array(
					'product'  => $data->product,
					'quantity' => $data->quantity,
					'price'    => $data->price,
				);
				$item = new OrderItem((object)$itemData);

				$item->setOrder($order);

				$em->persist($item);
				$em->flush();

				$itemData['id'] = $item->getId();

				$response = array('success' => true, 'item' => $itemData);
			}

		}


		return new JsonModel($response);
	}
	

	public function removeItemAction() {

		$response = array();

		$request = $this->getRequest();
		if ($request->isPost()) {
			
			$em = $this->getEntityManager();

			$data = $request->getPost();

			$item = $em->getRepository('Application\Entity\OrderItem')->find($data->id);
			
			$em->remove($item);
			$em->flush();

			$response = array('success' => true, 'item' => $data);        

		}
		return new JsonModel($response);
	}


	public function updateItemAction() {

		$response = array();

		$request = $this->getRequest();
		if ($request->isPost()) {
			
			$em = $this->getEntityManager();

			$data = $request->getPost();

			$item = $em->getRepository('Application\Entity\OrderItem')->find($data->id);

			$item->setData($data);	        

			$em->persist($item);
			$em->flush();

			$response = array('success' => true, 'item' => $data);        

		}
		return new JsonModel($response);
	}



	public function deleteOrderAction() {

		$response = array();

		$request = $this->getRequest();
		if ($request->isPost()) {

			$em = $this->getEntityManager();		    

			$order_id = $this->params('id');
			$order = $em->getRepository('Application\Entity\Order')->find($order_id);

			if ($order) {

				$em->remove($order);
				$em->flush();

				$response = array('success' => true, 'item' => $data);  
			}      

		}
		return new JsonModel($response);
	}
}
