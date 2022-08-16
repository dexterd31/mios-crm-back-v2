<?php

namespace App\Jobs;

use App\Managers\ReportManager;

class FormReport extends Job
{
    protected $formAnswerLogsIds;
    protected $plantillaRespuestas;
    protected $inputReport;
    protected $dependencies;
    protected $adviserInfo;
    protected $titleHeaders;
    protected $formId;
    protected $includeTipificationTime;
    protected $rrhhIdToNotify;

    public $timeout = 9999999;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $formAnswerLogsIds, array $plantillaRespuestas, array $inputReport, array $dependencies, array $adviserInfo, array $titleHeaders, $formId, $includeTipificationTime, $rrhhIdToNotify)
    {
        $this->formAnswerLogsIds = $formAnswerLogsIds;
        $this->plantillaRespuestas = $plantillaRespuestas;
        $this->inputReport = $inputReport;
        $this->dependencies = $dependencies;
        $this->adviserInfo = $adviserInfo;
        $this->titleHeaders = $titleHeaders;
        $this->formId = $formId;
        $this->includeTipificationTime = $includeTipificationTime;
        $this->rrhhIdToNotify = $rrhhIdToNotify;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ReportManager)->buildReportDataStructure($this->formAnswerLogsIds, $this->plantillaRespuestas, $this->inputReport, $this->dependencies, $this->adviserInfo, $this->titleHeaders, $this->formId, $this->includeTipificationTime, $this->rrhhIdToNotify);
    }
}
