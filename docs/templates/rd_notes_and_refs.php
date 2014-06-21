<section class="row" id="notes-and-references">
    <div class="col-xs-12">
        <h4><?php _e('Notes and References','docs'); ?></h4>
        <ol>
            <?php $i=1; foreach(RMTemplate::get()->get_var('references') as $ref): ?>
                <li id="note-<?php echo $i; ?>"><a name="note-<?php echo $i; ?>"></a><a href="#top<?php echo $i; ?>">&uarr;</a> <?php echo $ref['text']; ?></li>
                <?php $i++; endforeach; ?>
        </ol>
    </div>
</section>