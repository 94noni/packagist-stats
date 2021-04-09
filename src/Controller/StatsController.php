<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatsController extends AbstractController
{
    /**
     * @Route("", name="stats_index")
     */
    public function index(Request $request, HttpClientInterface $httpClient, ChartBuilderInterface $chartBuilder): Response
    {
        try {
            // query params handling
            $from = $request->query->get('f', \date('Y-m', \strtotime('-3 year')));
            if (!\preg_match('/^(20[0-9]{2})-(0[1-9]|1[0-2])$/', $from)) {
                $this->addFlash('danger', 'Invalid date from');

                return $this->redirectToRoute('stats_index');
            }
            $to = $request->query->get('t', \date('Y-m'));
            if (!\preg_match('/^(20[0-9]{2})-(0[1-9]|1[0-2])$/', $to)) {
                $this->addFlash('danger', 'Invalid date to');

                return $this->redirectToRoute('stats_index');
            }
            $p = $request->query->get('p', []);
            if (!\is_array($p) || empty($p)) {
                $p = ['symfony/console', 'symfony/stopwatch', 'symfony/http-client', 'symfony/uid'];
            }
            $colors = [
                'blue' => 'rgb(54, 162, 235)',
                'red' => 'rgb(255, 99, 132)',
                'green' => 'rgb(75, 192, 192)',
                'yellow' => 'rgb(255, 205, 86)',
                'purple' => 'rgb(153, 102, 255)',
                'orange' => 'rgb(255, 159, 64)',
                'grey' => 'rgb(201, 203, 207)',
            ];
            if (\count($p) > \count($colors)) {
                $this->addFlash('info', 'To much packages to compare');

                return $this->redirectToRoute('stats_index');
            }
            $average = 'monthly';

            $packages = [];
            $httpResponses = [];

            // fetch info (maybe go async)
            foreach ($p as $package) {
                $packages[$package] = [];
                $httpResponses['detail'][$package] = $httpClient->request('GET', \sprintf('https://packagist.org/packages/%s.json', $package), ['timeout' => 10]);
                $httpResponses['downloads'][$package] = $httpClient->request('GET', \sprintf('https://packagist.org/packages/%s/stats/all.json?average=%s&from=%s&to=%s', $package, $average, $from, $to), ['timeout' => 10]);
            }
            // compute info
            foreach ($httpResponses['detail'] as $package => $response) {
                $d = $response->toArray();
                $p = $d['package'];
                $packages[$package]['name'] = $p['name'];
                $packages[$package]['desc'] = $p['description'];
                $packages[$package]['url'] = $p['repository'];
                $packages[$package]['since'] = $p['time'];
                $packages[$package]['downloads'] = $p['downloads'];
                $packages[$package]['color'] = \array_shift($colors);
            }
            $datasets = [];
            foreach ($httpResponses['downloads'] as $package => $response) {
                $d = $response->toArray();
                $datasets[] = ['label' => $package, 'data' => $d['values'][$package], 'borderColor' => $packages[$package]['color']];
                $labels = $d['labels']; // maybe unsure all labels are always present/filled
            }
            // build the chart
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
            $chart->setData(['labels' => $labels, 'datasets' => $datasets,]);
            $chart->setOptions([
                'scales' => ['yAxes' => [['ticks' => ['min' => 0]],],],
            ]);
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Error handling data');

            return $this->redirectToRoute('stats_index');
        }

        return $this->render('stats/index.html.twig', [
            'packages' => $packages,
            'chart' => $chart,
            'comparison' => [
                'packages' => \implode(' ', \array_keys($packages)),
                'from' => $from,
                'to' => $to,
                'average' => $average,
            ],
        ]);
    }
}
