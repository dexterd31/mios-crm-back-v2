<?php

use App\Http\Controllers\RolCrmController;
use Illuminate\Http\Request;
use App\Models\Permission;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;
use App\Http\Controllers\PermissionController;

class PermissionControllerTest extends TestCase
{
    use CreatesApplication;

    /**
	* @dataProvider dataProviderIndex
	* @covers App\Http\Controllers\PermissionController
	* 
	**/
    public function test_index($permissions, $resultExpected)
    {
        $permissionController = new PermissionController();

        $permissionModel = $this->getMockBuilder(Permission::class)
            ->addMethods(array("where", "get"))
            ->disableOriginalConstructor()
            ->getMock();

        $permissionModel->method("where")
            ->willReturn($permissionModel);

        $permissionModel->method("get")
            ->willReturn($permissions);

        $permissionController->setPermissionModel($permissionModel);

        $result = $permissionController->index(1);
        $this->assertEquals($result, $resultExpected);
    }

    public function dataProviderIndex()
    {
        $permissions = (object)[
            (object)[
                'module_id' => 1,
                'actionPermissions' => (object)[
                    'action' => "view"
                ]
            ]
        ];

        $resultExpected  = [
            "RoleId" => "1",
            "1" => [
                "view"
            ]
        ];

        $resultExpectedPermissionsEmpty = [
            "RoleId" => "1"
        ];
        return [
            [[], $resultExpectedPermissionsEmpty],
            [$permissions, $resultExpected]
        ];
    }

    /**
	* @dataProvider dataProviderCreate
	* @covers App\Http\Controllers\PermissionController
	* 
	**/
    public function test_create($request)
    {
        $permissionController = new PermissionController();

        $permissionModel = $this->getMockBuilder(Permission::class)
            ->addMethods(array("insert"))
            ->disableOriginalConstructor()
            ->getMock();

        $permissionController->setPermissionModel($permissionModel);

        $result = $permissionController->create($request);
        $this->assertNull($result);
    }

    public function dataProviderCreate()
    {
        $permissions = new Request();
        $permissions->replace([
            'idRole' => 0,
            "roles" =>
            [
                [
                    "module_id" => 3,
                    "actions_permission_id" => [1,2,3]
                ],
                [
                    "module_id" => 2,
                    "actions_permission_id" => [1,2,3]
                ]
            ]
        ]);
        return [
            [$permissions]
        ];
    }

    /**
	* 
	* @covers App\Http\Controllers\PermissionController
	* 
	**/
    public function test_edit()
    {
        $permissionControllerStub = $this->getMockBuilder(PermissionController::class)
            ->onlyMethods(array("create"))
            ->getMock();

        $permissionModel = $this->getMockBuilder(Permission::class)
            ->addMethods(array("where"))
            ->onlyMethods(["delete"])
            ->disableOriginalConstructor()
            ->getMock();

        $permissionModel->method("where")
            ->willReturn($permissionModel);

        $permissionControllerStub->setPermissionModel($permissionModel);

        $request = new Request();
        $result = $permissionControllerStub->edit($request);
        $this->assertNull($result);
    }

    /**
	* @covers App\Http\Controllers\PermissionController
	* 
	**/
    public function test_getPermissionModel()
    {
        $PermissionController = new PermissionController();
        $Permission = $PermissionController->getPermissionModel();
        $this->assertInstanceOf(Permission::class, $Permission);
    }
}
