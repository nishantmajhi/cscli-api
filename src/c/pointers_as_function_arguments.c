/* Unit 9: Pointers
 * Pointers as function arguments - modifies the caller's variable
 * through the pointer, and a function returning a pointer. */
#include <stdio.h>

void doubleValue(int *n) { /* pointer as function argument */
    *n = *n * 2;
}

int *findMax(int arr[], int n) { /* function returning a pointer */
    int *maxPtr = &arr[0];
    for (int i = 1; i < n; i++) {
        if (arr[i] > *maxPtr) {
            maxPtr = &arr[i];
        }
    }
    return maxPtr;
}

int main(void) {
    int x = 21;
    doubleValue(&x);
    printf("Doubled value = %d\n", x);

    int arr[] = {3, 9, 2, 7, 5};
    int *max = findMax(arr, 5);
    printf("Maximum value = %d\n", *max);

    return 0;
}
