<?php
/**
 * Created by PhpStorm.
 * User: pavel
 * Date: 14/07/16
 * Time: 10:35
 */

namespace AppBundle\Controller;

use AppBundle\Internal\Log;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use \Exception;

final class SyslogController extends Controller
{
	/**
	 * @Route("/read/{container}/{start}/{step}", name="read")
	 */
	public function readAction($container, $start = 10, $step = 0)
	{
		try {
			$this->checkDocker();
			$count = $start * ($step + 1);
			$logs = [];
			exec("docker exec -i $container tail -n {$count} /var/log/syslog {$this->getSlice($start * $step)} 2>&1", $logs);

			return
				$this->render(
					'syslog/read.html.twig',
					[
						'logs' => $this->extractLogs($logs),
						'container' => $container,
						'start' => $start,
						'step' => $step,
					]
				);
		} catch (Exception $e) {
			return new Response($e->getMessage());
		}
	}

	/**
	 * @Route("/list", name="list")
	 */
	public function listAction()
	{
		try {
			$this->checkDocker();
			$containers = [];
			exec('docker ps -a', $containers);
			foreach ($containers as $key => $container) {
				preg_match('/.*(?P<name>\s.+$)/', $container, $matches);
				$containers[$key] = $matches['name'];
			}

			return $this->render('syslog/list.html.twig', [
				'containers' => array_slice($containers, 1)
			]);
		} catch (Exception $e) {
			return new Response($e->getMessage());
		}
	}

	/**
	 * @throws Exception
	 */
	private function checkDocker()
	{
		empty(exec('which docker'))
			? $this->dockerException()
			: true
		;
	}

	/**
	 * @throws Exception
	 */
	private function dockerException()
	{
		throw new Exception('Install Docker first!');
	}

	/**
	 * @param string $slice
	 * @return string
	 */
	private function getSlice($slice)
	{
		if ($slice) {
			return "| head -$slice";
		}

		return '';
	}

	/**
	 * @param array $logs
	 * @return array
	 */
	private function extractLogs(array $logs)
	{
		foreach ($logs as $key => $logUnit) {
			preg_match('/(?P<date>\w{3,}\s\d{2,}\s\d{2,}:\d{2,}:\d{2,}).+(?P<id>\[\d+\]).+(?P<level>INFO|WARNING|ERROR):\s(?P<message>.*)\s\{.*/', $logUnit, $matches);

			$log = new Log();
			if (count($matches) >= 5) {
				$log->id = $matches['id'];
				$log->date = $matches['date'];
				$log->level = $matches['level'];
				$log->message = $matches['message'];
			} else {
				$log->message = $logUnit;
			}

			$logs[$key] = $log;
		}

		return $logs;
	}
}