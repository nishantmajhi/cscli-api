/* Unit 7: Functions
 * Contrasts passing arguments by value (no change to caller) with
 * passing by address/pointer (caller's values are modified) using a
 * swap function. */
#include <stdio.h>

void swapByValue(int a, int b) {
    int temp = a;
    a = b;
    b = temp;
    printf("Inside swapByValue: a = %d, b = %d\n", a, b);
}

void swapByAddress(int *a, int *b) {
    int temp = *a;
    *a = *b;
    *b = temp;
}

int main(void) {
    int x = 10, y = 20;

    swapByValue(x, y);
    printf("After swapByValue, in main: x = %d, y = %d (unchanged)\n", x, y);

    swapByAddress(&x, &y);
    printf("After swapByAddress, in main: x = %d, y = %d (changed)\n", x, y);

    return 0;
}
