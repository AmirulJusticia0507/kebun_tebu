import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);

    const isAdmin = computed(() => user.value?.role === 'admin');
    const isFieldOfficer = computed(() => user.value?.role === 'field_officer');

    function setUser(userData) {
        user.value = userData;
    }

    function clearUser() {
        user.value = null;
    }

    return { user, isAdmin, isFieldOfficer, setUser, clearUser };
});
