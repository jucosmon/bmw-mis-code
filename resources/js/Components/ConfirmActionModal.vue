<script setup>
import { defineEmits, defineProps, onMounted, ref, watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'submit']);
const password = ref('');
const errorMessage = ref('');
const dialogRef = ref(null);

// Watch for changes in the show prop to open/close the dialog
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      dialogRef.value?.showModal();
      document.body.classList.add('blur'); // Add blur class to body
    } else {
      dialogRef.value?.close();
      document.body.classList.remove('blur'); // Remove blur class from body
    }
  }
);

// Optional: If you want to handle the dialog on mount
onMounted(() => {
  if (props.show) {
    dialogRef.value?.showModal();
    document.body.classList.add('blur'); // Add blur class to body

  }
});

const close = () => {
  emit('close');
  document.body.classList.remove('blur'); // Remove blur class from body

};

const submit = () => {
  if (password.value === '') {
    errorMessage.value = 'Password is required.';
  } else {
    emit('submit', password.value);
  }
};

</script>

<template>
    <dialog ref="dialogRef" class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent">
      <div class="fixed inset-0 z-50 flex justify-center items-center px-4 py-6 sm:px-0">
        <div class="relative bg-white rounded-lg shadow-lg max-w-2xl w-full">
          <div class="p-6">
            <h3 class="text-lg font-semibold">Enter Your Admin Password</h3>
            <input
              type="password"
              v-model="password"
              class="w-full mt-4 p-2 border border-gray-300 rounded"
              placeholder="Your Admin Password"
              required
            />
            <div v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</div>
            <div class="mt-4 flex justify-end space-x-2">
              <button @click="close" class="text-gray-500">Cancel</button>
              <button @click="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Confirm</button>
            </div>
          </div>
        </div>
      </div>
    </dialog>
  </template>

<style>
.blur {
  filter: blur(10px); /* Adjust the blur amount as needed */
  transition: filter 0.3s ease; /* Smooth transition for the blur effect */
}
</style>
