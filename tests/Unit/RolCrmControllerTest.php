<?php

use App\Http\Controllers\RolCrmController;
use Illuminate\Http\Request;
use App\Http\Controllers\PermissionCrmController;
use App\Services\NominaService;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;
use App\Models\User;
class RolCrmControllerTest extends TestCase
{
    use CreatesApplication;

    /**
	* @dataProvider dataProviderIndex
	* @covers App\Http\Controllers\RolCrmController
	* 
	**/
    public function test_index($request)
    {
        $rolCrmControllerStub = $this->getMockBuilder(RolCrmController::class)
            ->onlyMethods(array("saveModel"))
            ->getMock();
        
        $rolCrm = (object)['id' => 1];
        $rolCrmControllerStub->method("saveModel")
            ->willReturn($rolCrm);

        $permissionCrmControllerStub = $this->getMockBuilder(PermissionCrmController::class)
            ->onlyMethods(array("createPermissionCrm"))
            ->disableOriginalConstructor()
            ->getMock();

        $rolCrmControllerStub->setPermissionCrmController($permissionCrmControllerStub);

        $result = $rolCrmControllerStub->createRolCrm($request);
        $this->assertNull($result);
    }

    public function dataProviderIndex()
    {
        $requestNotMenu = new Request();
        $requestNotMenu->replace([
            'roles' => [
                'id' => 1,
                'name' =>"role",
                'key' =>"role",
                'menu_ids' => []
        ]]);

        $requestMotKeyMenu = new Request();
        $requestMotKeyMenu->replace([
            'roles' => [
                'id' => 1,
                'name' =>"role",
                'key' =>"role",
        ]]);

        $request = new Request();
        $request->replace([
            'roles' => [
                'id' => 1,
                'name' =>"role",
                'key' =>"role",
                'menu_ids' => [1,2,3]
        ]]);

        return [
            [$request],
            [$requestMotKeyMenu],
            [$requestNotMenu]
        ];
    }

    /**
	* @covers App\Http\Controllers\RolCrmController
	* 
	**/
    public function test_getPermissionCrmController()
    {
        $rolCrmController = new RolCrmController();
        $permissionCrmController = $rolCrmController->getPermissionCrmController();
        $this->assertInstanceOf(PermissionCrmController::class, $permissionCrmController);
    }
}
