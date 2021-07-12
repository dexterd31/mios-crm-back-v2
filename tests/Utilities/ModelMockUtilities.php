<?php

namespace Tests\Utilities;

class ModelMockUtilities
{
	public $result; 
	public function where($model, $foo = null)
	{
		if(is_callable($model))
		{
			$model($this);
		}
		return $this;
	}

	public function whereIn($model, $foo = null)
	{
		if(is_callable($model))
		{
			$model($this);
		}
		return $this;
	}

	public function orWhere($model, $foo)
	{
		return $this;
	}

	public function limit($model)
	{
		return $this;
	}

	public function orderBy($colun, $tipe)
	{
		return $this;
	}


	public function get()
	{
		return $this->result;
	}

	public function first()
	{
		return $this->result;
	}

	public function find()
	{
		return $this->result;
	}

	public function firstOrFail()
	{
		return $this->result;
	}

	public function save()
	{
		return $this->result;
	}
}

?>