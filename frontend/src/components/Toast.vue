/* Copyright (C) 2022 Manticore Software Ltd
* You may use, distribute and modify this code under the
* terms of the AGPLv3 license.
*
* You can find a copy of the AGPLv3 license here
* https://www.gnu.org/licenses/agpl-3.0.txt
*/
<template>
  <div aria-live="polite" aria-atomic="true" style="position: relative;">
    <div class="toast hide" style="position: absolute; top: 10px; right: 10px; z-index: 1;">
      <div class="toast-header">
        <strong class="mr-auto">DB benchmarks</strong>
        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body">
        <strong>API call error occurred:</strong> {{ error }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    error: {
      type: String,
      required: true
    },
  },
}
</script>
