<?php

use PHPUnit\Framework\TestCase;

final class FrontendTest extends TestCase {

    public function test_group_items_by_theme_preserves_first_seen_order() {
        $items = array(
            array( 'question' => 'Q1', 'answer' => 'A1', 'group' => 'Livraison' ),
            array( 'question' => 'Q2', 'answer' => 'A2', 'group' => 'Produit' ),
            array( 'question' => 'Q3', 'answer' => 'A3', 'group' => 'Livraison' ),
        );

        $groups = navi_faq_group_items_by_theme( $items );

        $this->assertSame( array( 'Livraison', 'Produit' ), array_keys( $groups ) );
        $this->assertCount( 2, $groups['Livraison'] );
        $this->assertCount( 1, $groups['Produit'] );
    }

    public function test_group_items_by_theme_buckets_untitled_items_under_empty_key() {
        $items = array(
            array( 'question' => 'Q1', 'answer' => 'A1', 'group' => '' ),
            array( 'question' => 'Q2', 'answer' => 'A2' ), // pas de clé 'group' du tout (données historiques)
        );

        $groups = navi_faq_group_items_by_theme( $items );

        $this->assertSame( array( '' ), array_keys( $groups ) );
        $this->assertCount( 2, $groups[''] );
    }

    public function test_group_items_by_theme_trims_whitespace_only_themes() {
        $items = array(
            array( 'question' => 'Q1', 'answer' => 'A1', 'group' => '   ' ),
        );

        $groups = navi_faq_group_items_by_theme( $items );

        $this->assertSame( array( '' ), array_keys( $groups ) );
    }
}
