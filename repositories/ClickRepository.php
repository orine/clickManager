<?php
namespace app\repositories;

use \app\models\Click;
use app\repositories\interfaces\iClickRepository;
use app\services\builder\ClickBuilder;
use yii\db\Query;

class ClickRepository implements iClickRepository
{
	private $dbConnection;

	public function __construct(\yii\db\Connection $connection)
	{
		$this->dbConnection = $connection;
	}

	/**
	 * @param Click $click
	 * @return bool
	 * @throws \Exception
	 */
	public function create(Click $click)
	{
		try {
			$this->dbConnection->createCommand()
				->insert('click', $this->_setClickParams($click))
				->execute();
		} catch (\Exception $e) {
			throw new \Exception('Cant save click');
		}

		return true;
	}

	/**
	 * TODO::need update only changed fields!
	 * @param Click $click
	 * @return bool
	 * @throws \Exception
	 */
	public function update(Click $click)
	{
		try {
			$this->dbConnection->createCommand()
				->update('click', $this->_setClickParams($click), ['id' => $click->getId()])
				->execute();
		} catch (\Exception $e) {
			throw new \Exception('Cant update click');
		}

		return true;
	}

	/**
	 * @param $id
	 * @return Click | null
	 */
	public function findOne($id)
	{
		$command = $this->dbConnection->createCommand(
			'SELECT id, ua, INET_NTOA(ip) as ip, ref, param1, param2, error, bad_domain FROM click WHERE id=:id'
		);
		$command->bindValue(':id', $id);
		$click = $command->queryOne();

		if (empty($click)) {
			return null;
		}

		return (new ClickBuilder($click))->build();
	}


	/**
	 * @param Click $click
	 * @return Click
	 */
	public function findUnique(Click $click)
	{
		$uniqueClick =  $this->find([
			'ua' => $click->getUserAgent(), 'ip' => ip2long($click->getIp()),
			'ref' => $click->getReferral(), 'param1' => $click->getParam1()
		]);

		return $uniqueClick[0] ?? null;
	}

	/**
	 * @param array $filters
	 * @param bool $isArray
	 * @return array Click objects|array|null
	 */
	public function find($filters = [], $isArray = false)
	{
		$clicks = new Query();
		$clicks->from('click');
		$clicks->select(['id', 'ua', 'INET_NTOA(ip) as ip', 'ref', 'param1', 'param2', 'error', 'bad_domain']);
		$clicks->where($filters);

		$result = $clicks->all();

		if (empty($result)) {
			return null;
		}

		if ($isArray === false) {
			return array_map(function ($item) {
				return (new ClickBuilder($item))->addParams()->build();
			}, $result);
		}

		return $result;
	}

	protected function _setClickParams(Click $click)
	{
		return [
			'id' => $click->getId(),
			'ua' => $click->getUserAgent(),
			'ip' => ip2long($click->getIp()),
			'ref' => $click->getReferral(),
			'param1' => $click->getParam1(),
			'param2' => $click->getParam2(),
			'error' => $click->getError(),
			'bad_domain' => $click->getBadDomain()
		];
	}

}