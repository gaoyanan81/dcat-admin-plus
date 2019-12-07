<?php

namespace Dcat\Admin\Grid\Concerns;

use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Exporter;
use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Dcat\Admin\Grid\Tools;

trait HasExporter
{
    /**
     * @var Exporter
     */
    protected $exporter;

    /**
     * Export driver.
     *
     * @var string|Grid\Exporters\AbstractExporter
     */
    protected $exportDriver;

    /**
     * Set exporter driver for Grid to export.
     *
     * @param string|Grid\Exporters\AbstractExporter|array $exporter
     *
     * @return Grid\Exporters\AbstractExporter
     */
    public function exporter($exporter = null)
    {
        $titles = [];

        if (is_array($exporter) || $exporter === false) {
            $titles = $exporter;
            $exporter = null;
        }

        $this->showExporter();

        $driver = $this->exportDriver ?: ($this->exportDriver = $this->getExporter()->resolve($exporter));

        return $driver->titles($titles);
    }

    /**
     * Handle export request.
     *
     * @param bool $forceExport
     *
     * @return mixed
     */
    protected function handleExportRequest($forceExport = false)
    {
        if (!$scope = request($this->getExporter()->getQueryName())) {
            return;
        }

        if ($this->builder) {
            call_user_func($this->builder, $this);

            $this->builder = null;
        }

        // clear output buffer.
        if (ob_get_length()) {
            ob_end_clean();
        }

        if ($forceExport || $this->allowExporter()) {
            return $this->resolveExportDriver($scope)->export();
        }
    }

    /**
     * @return Exporter
     */
    public function getExporter()
    {
        return $this->exporter ?: ($this->exporter = new Exporter($this));
    }

    /**
     * @param string $gridName
     */
    public function setExporterQueryName($gridName)
    {
        if (!$this->allowExporter()) {
            return;
        }

        $this->getExporter()->setQueryName($gridName.'_export_');
    }

    /**
     * @param string $scope
     *
     * @return AbstractExporter
     */
    protected function resolveExportDriver($scope)
    {
        if (!$this->exportDriver) {
            $this->exportDriver = $this->getExporter()->resolve();
        }

        return $this->exportDriver->withScope($scope);
    }

    /**
     * Get the export url.
     *
     * @param int  $scope
     * @param null $args
     *
     * @return string
     */
    public function getExportUrl($scope = 1, $args = null)
    {
        $input = array_merge(request()->all(), $this->getExporter()->formatExportQuery($scope, $args));

        if ($constraints = $this->model()->getConstraints()) {
            $input = array_merge($input, $constraints);
        }

        return $this->getResource().'?'.http_build_query($input);
    }

    /**
     * Render export button.
     *
     * @return string
     */
    public function renderExportButton()
    {
        if (!$this->allowExporter()) {
            return '';
        }

        return (new Tools\ExportButton($this))->render();
    }

    /**
     * Disable export.
     *
     * @return $this
     */
    public function disableExporter(bool $disable = true)
    {
        return $this->option('show_exporter', !$disable);
    }

    /**
     * Show export button.
     *
     * @return $this
     */
    public function showExporter(bool $val = true)
    {
        return $this->disableExporter(!$val);
    }

    /**
     * If grid show export btn.
     *
     * @return bool
     */
    public function allowExporter()
    {
        return $this->options['show_exporter'];
    }
}
