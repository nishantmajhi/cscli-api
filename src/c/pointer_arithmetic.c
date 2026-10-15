/* Unit 9: Pointers
 * Pointer arithmetic: ++, --, +, - on pointers move by sizeof(type). */
#include <stdio.h>

int main(void) {
    int arr[5] = {10, 20, 30, 40, 50};
    int *p = arr;

    for (int i = 0; i < 5; i++) {
        printf("*(p + %d) = %d  (address %p)\n", i, *(p + i), (void *)(p + i));
    }

    return 0;
}
