<?php
namespace Gss\EmailEvent\Setup;

class UpgradeSchema implements \Magento\Framework\Setup\UpgradeSchemaInterface
{

    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup, 
        \Magento\Framework\Setup\ModuleContextInterface $context
    )
	{
		$installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('gss_emailevent_send_email')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('gss_emailevent_send_email')
			)
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					'ID'
				)
				->addColumn(
					'email',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable => false'],
					'Email'
				)
				->addColumn(
					'post_check',
                    \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Post Check'
				)
				->addColumn(
					'email_template_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
                    [],
					'Email Template Id'
                )
                ->addColumn(
                    'date_to_send',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Send Date'
                )
				->addColumn(
						'created_at',
						\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
						null,
						['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
						'Created At'
                )
                ->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Updated At')
				->setComment('Updated At');
			$installer->getConnection()->createTable($table);
		}

		if (version_compare($context->getVersion(), '1.0.2') < 0) {
			$tableName = $setup->getTable('gss_emailevent_send_email');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                // Declare data
                $columns = [
                    'first_name' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'comment' => 'First Name',
					],
					'last_name' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'comment' => 'Last Name',
                    ],
                ];

                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }

            }
		}

		if (version_compare($context->getVersion(), '1.0.3') < 0) {
			$tableName = $setup->getTable('gss_emailevent_send_email');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                // Declare data
                $columns = [
                    'customer_id' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'comment' => 'Customer Id',
					]
                ];

                $connection = $setup->getConnection();
                foreach ($columns as $name => $definition) {
                    $connection->addColumn($tableName, $name, $definition);
                }

            }
		}
		$installer->endSetup();
	}
}
