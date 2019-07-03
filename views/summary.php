<?php
    // variables
    $id = $id ?: null;
    $total_saved_bytes = $total_saved_bytes ?: null;
    $total_savings_percentage = $total_savings_percentage ?: null;
    $total_savings = $total_savings ?: null;
    $detailed_results_html = $detailed_results_html ?: null;
?>

<?php if($total_saved_bytes) { ?>
    <div class="tinyga-result-wrap">
        Saved <?php echo "{$total_savings_percentage} ($total_savings)" ?>
        <br />
        <small class="tinyga-item-details"
               data-id="<?php echo $id ?>"
               original-title="<?php echo $detailed_results_html ?>">
            Show details
        </small>
    </div>
<?php } else { ?>
    No savings
<?php } ?>
