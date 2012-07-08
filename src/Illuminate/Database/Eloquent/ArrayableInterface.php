<?php namespace Illuminate\Database\Eloquent;

interface ArrayableInterface {

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray();

}