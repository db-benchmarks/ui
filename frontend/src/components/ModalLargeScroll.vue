<template>
  <div>
    <!-- Button trigger modal -->
    <slot name="trigger"></slot>
    <!-- Modal -->
    <div class="modal fade" id="modal-long" tabindex="-1" role="dialog" aria-labelledby="ModalLongTitle"
         aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <slot name="header"></slot>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body"><slot name="content"></slot></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ModalLargeScroll"
}
</script>

<style scoped>
a {
  cursor: pointer;
}
</style>
