<?php

use App\Http\Controllers\CampaignController;
use Illuminate\Http\Request;
use App\Services\CiuService;
use App\Services\NominaService;
use PHPUnit\Framework\TestCase;
use App\Models\User;
class CampaignControllerTest extends TestCase
{

    /**
	* @dataProvider dataProviderIndex
	* @covers App\Http\Controllers\CampaignController
	* 
	**/
    public function test_index($user, $status)
    {
        $campaignControStub = $this->getMockBuilder(CampaignController::class)
            ->onlyMethods(array("authUser"))
            ->getMock();
        $campaignControStub->method("authUser")
            ->willReturn($user);

        $ciuServiceStub = $this->getMockBuilder(CiuService::class)
            ->onlyMethods(array("fetchUser"))
            ->disableOriginalConstructor()
            ->getMock();

        $user = (object)[
            'data' =>
                (object)[
                'rrhh' => (object)[
                    'campaign_id' => 1
                ]
        ]];
        $ciuServiceStub->method("fetchUser")
			->willReturn($user);

        $nominaServiceStub = $this->getMockBuilder(NominaService::class)
            ->onlyMethods(["fetchSpecificCampaigns"])
            ->disableOriginalConstructor()
            ->getMock();

        $campaigns = (object)[
            'data' => [
                (object)[
                    'rrhh_id' => 1,
                    'code' => 1,
                    'created_at' => 1,
                    'updated_at' => 1,
                ],
                (object)[
                    'rrhh_id' => 2,
                    'code' => 1,
                    'created_at' => 1,
                    'updated_at' => 1,
                ],
                (object)[
                    'rrhh_id' => 3,
                    'code' => 1,
                    'created_at' => 1,
                    'updated_at' => 1,
                ],
            ]
        ];

        $nominaServiceStub->method("fetchSpecificCampaigns")->willReturn($campaigns);

        $campaignControStub->setNominaService($nominaServiceStub);
        $campaignControStub->setCiuService($ciuServiceStub);

        $result = $campaignControStub->index();
        $this->assertEquals($result->status(), $status);
    }

    public function dataProviderIndex()
    {
        $user = new User();
        $user->rrhh_id = 1;
        $user->id = 1;
        return [
            [$user, 200],
            [[], 500]
        ];
    }
}
