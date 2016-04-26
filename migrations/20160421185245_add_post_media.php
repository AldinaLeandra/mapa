<?php

use Phinx\Migration\AbstractMigration;

class AddPostMedia extends AbstractMigration
{
    public function change()
    {
		$this->table('post_media')
            ->addColumn('post_id', 'integer')
            ->addColumn('form_attribute_id', 'integer')
            ->addColumn('value', 'integer', ['null' => true])
            ->addColumn('created', 'integer', ['default' => 0])
			->addForeignKey('value', 'media', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                ])
            ->create()
			;
    }
}
