<?php

use PHPUnit\Framework\TestCase;

final class DataTest extends TestCase {

    public function test_sanitize_items_keeps_complete_rows() {
        $items = navi_faq_sanitize_items(
            array( 'Livrez-vous à l\'international ?' ),
            array( 'Oui, dans toute l\'UE.' ),
            array( 'Commandes & Livraison' )
        );

        $this->assertCount( 1, $items );
        $this->assertSame( 'Livrez-vous à l\'international ?', $items[0]['question'] );
        $this->assertSame( 'Oui, dans toute l\'UE.', $items[0]['answer'] );
        $this->assertSame( 'Commandes & Livraison', $items[0]['group'] );
    }

    public function test_sanitize_items_discards_rows_missing_question_or_answer() {
        $items = navi_faq_sanitize_items(
            array( '', 'Question sans réponse', 'Question complète' ),
            array( 'Réponse orpheline', '', 'Réponse complète' )
        );

        $this->assertCount( 1, $items );
        $this->assertSame( 'Question complète', $items[0]['question'] );
    }

    public function test_sanitize_items_defaults_group_to_empty_string() {
        $items = navi_faq_sanitize_items(
            array( 'Question' ),
            array( 'Réponse' )
        );

        $this->assertSame( '', $items[0]['group'] );
    }

    public function test_sanitize_items_ignores_extra_group_entries_without_matching_question() {
        $items = navi_faq_sanitize_items(
            array( 'Question' ),
            array( 'Réponse' ),
            array( 'Thème A', 'Thème orphelin' )
        );

        $this->assertCount( 1, $items );
        $this->assertSame( 'Thème A', $items[0]['group'] );
    }
}
