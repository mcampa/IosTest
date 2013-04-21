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
use Zend\View\Model\ViewModel;

use Application\Entity\OrderItem;
use Application\Entity\Order;

class iosController extends EntityUsingController
{

	public function indexAction()
	{

		$em = $this->getEntityManager();

		$orders = $em->getRepository('Application\Entity\Order')->findBy(array(), array('id' => 'ASC'));

		$response = array('orders' => $orders);

		return new ViewModel($response);
	}
	
	public function newAction()
	{	
		$order = new Order;

		$em = $this->getEntityManager();

		$response = array();

		$request = $this->getRequest();
		if ($request->isPost()) {
			
			$data = $request->getPost();

			$order->setCreatedDate();
			$order->setCustomer($data->customer);	
			$order->setTotal(0);		    

			$em->persist($order);
			$em->flush();

			return $this->redirect()->toRoute('ios', array('action' => 'order', 'id' => $order->getId()));
		}
		

		
			return $this->redirect()->toRoute('ios');

	}

	public function orderAction()
	{	
		$em = $this->getEntityManager();
		
		$order_id = $this->params('id');
		$order = $em->getRepository('Application\Entity\Order')->find($order_id);

		return new ViewModel(array('order' => $order));
	}
	
	public function printAction() {

		$em = $this->getEntityManager();

		$order_id = $this->params('id');
		$order = $em->getRepository('Application\Entity\Order')->find($order_id);

		$view = new ViewModel(array('order' => $order));
		$view->setTerminal(true);	
		return $view;    	
	}    

	public function downloadAction() {

		$em = $this->getEntityManager();

		$order_id = $this->params('id');
		$order = $em->getRepository('Application\Entity\Order')->find($order_id);


		$renderer = new \Zend\View\Renderer\PhpRenderer();

		$resolver = new \Zend\View\Resolver\TemplateMapResolver();
		$resolver->setMap(array(
			'download' => __DIR__.'/../../../view/application/ios/download.phtml'
		));

		$renderer->setResolver($resolver);

		$model = new ViewModel();
		$model->setTerminal(true);	
		$model->setVariable('order', $order);
		$model->setTemplate('download');

		ob_start();
		$html = $renderer->render($model);
		ob_clean();
	   
		$pdf = $this->xhtml2pdf();

		$pdf->addPage($html);

		$filename = "order_".sprintf('%04d', $order_id).".pdf";

		$pdf->send($filename);

		exit();

	}
}
