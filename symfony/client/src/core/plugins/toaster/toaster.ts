import {h, defineComponent, TransitionGroup, App, reactive, toRefs} from 'vue';
import ToastComponent from '@ohrm/oxd/core/components/Toast/Toast.vue';
import {
  TYPE_SUCCESS,
  TYPE_DEFAULT,
  TYPE_ERROR,
  TYPE_INFO,
  TYPE_WARN,
} from '@ohrm/oxd/core/components/Toast/types';
import {nanoid} from 'nanoid';

interface Toast {
  id: string;
  type: string;
  title: string;
  message: string;
  show: boolean;
}

interface ToastMessage {
  title: string;
  message: string;
}

interface ToasterOptions {
  animation?: string;
  class?: string;
  position?: string;
  persist?: boolean;
  duration?: number;
}

interface ToasterState {
  toasts: Toast[];
  transition: string;
  class: string;
  position: string;
}

export interface ToasterAPI {
  notify: (toast: Toast) => Promise<string>;
  show: (message: ToastMessage) => Promise<string>;
  success: (message: ToastMessage) => Promise<string>;
  error: (message: ToastMessage) => Promise<string>;
  info: (message: ToastMessage) => Promise<string>;
  warn: (message: ToastMessage) => Promise<string>;
  clear: (id: number | string) => void;
  clearAll: () => void;

  saveSuccess: () => Promise<string>;
  addSuccess: () => Promise<string>;
  updateSuccess: () => Promise<string>;
  deleteSuccess: () => Promise<string>;
  cannotDelete: () => Promise<string>;
  noRecordsFound: () => Promise<string>;
}

const state: ToasterState = reactive({
  toasts: [],
  transition: '',
  class: '',
  position: '',
});

const Toaster = defineComponent({
  name: 'oxd-toaster',
  setup() {
    return {
      ...toRefs(state),
    };
  },
  methods: {
    onUpdateShow(state: boolean, index: number) {
      if (state === false) {
        this.toasts.splice(index, 1);
      }
    },
  },
  computed: {
    classes(): object {
      return {
        'oxd-toast-container': true,
        [`oxd-toast-container--${this.position}`]: true,
      };
    },
  },

  render() {
    return h(
      TransitionGroup,
      {appear: true, name: this.transition, tag: 'div', class: this.classes},
      {
        default: () =>
          this.toasts.map((toast: Toast, index: number) => {
            return h(ToastComponent, {
              key: toast.id,
              type: toast.type,
              title: toast.title,
              message: toast.message,
              show: toast.show,
              class: this.class,
              'onUpdate:show': (state: boolean) =>
                this.onUpdateShow(state, index),
            });
          }),
      },
    );
  },
});

export default {
  install: (app: App, options: ToasterOptions) => {
    // Create toaster vdom element
    const toastWrapper = document.createElement('oxd-toaster');
    toastWrapper.id = 'oxd-toaster_1';
    (document.getElementById('app') as HTMLElement).appendChild(toastWrapper);

    // Toaster API
    const clear = (id: number | string): void => {
      if (typeof id === 'string') {
        const _index = state.toasts.findIndex(item => item.id === id);
        if (_index > -1) {
          clear(_index);
        }
      } else if (state.toasts[id]) {
        state.toasts.splice(id, 1);
      }
    };

    const notify = (toast: Toast): Promise<string> => {
      return new Promise(resolve => {
        const _id = nanoid(8);
        state.toasts.push({...toast, id: _id});
        if (!options.persist) {
          const _duration = options.duration ? options.duration : 2500;
          setTimeout(() => {
            clear(_id);
            resolve(_id);
          }, _duration);
        } else {
          resolve(_id);
        }
      });
    };

    const success = (message: ToastMessage): Promise<string> => {
      return notify({
        id: '', // Auto setting
        type: TYPE_SUCCESS,
        show: true,
        ...message,
      });
    };

    const error = (message: ToastMessage): Promise<string> => {
      return notify({
        id: '', // Auto setting
        type: TYPE_ERROR,
        show: true,
        ...message,
      });
    };

    const info = (message: ToastMessage): Promise<string> => {
      return notify({
        id: '', // Auto setting
        type: TYPE_INFO,
        show: true,
        ...message,
      });
    };

    const warn = (message: ToastMessage): Promise<string> => {
      return notify({
        id: '', // Auto setting
        type: TYPE_WARN,
        show: true,
        ...message,
      });
    };

    const show = (message: ToastMessage): Promise<string> => {
      return notify({
        id: '', // Auto setting
        type: TYPE_DEFAULT,
        show: true,
        ...message,
      });
    };

    const clearAll = (): void => {
      state.toasts = [];
    };

    const saveSuccess = () =>
      success({
        title: 'Success',
        message: 'Successfully Saved',
      });

    const addSuccess = () =>
      success({
        title: 'Success',
        message: 'Successfully Added',
      });

    const updateSuccess = () =>
      success({
        title: 'Success',
        message: 'Successfully Updated',
      });

    const deleteSuccess = () =>
      success({
        title: 'Success',
        message: 'Successfully Deleted',
      });

    const cannotDelete = () =>
      error({
        title: 'Error',
        message: 'Cannot be deleted',
      });

    const noRecordsFound = () =>
      info({
        title: 'Info',
        message: 'No Records Found',
      });

    state.class = options.class ? options.class : 'oxd-toast-container--toast';
    state.transition = options.animation ? options.animation : 'oxd-toast-list';
    state.position = options.position ? options.position : 'bottom';

    // Define Toaster component
    app.component('oxd-toaster', Toaster);

    // Add Toaster API to Vue global scope
    const toasterAPI: ToasterAPI = {
      notify,
      show,
      success,
      error,
      info,
      warn,
      clear,
      clearAll,
      saveSuccess,
      addSuccess,
      updateSuccess,
      deleteSuccess,
      cannotDelete,
      noRecordsFound,
    };
    app.config.globalProperties.$toast = toasterAPI;
  },
};
