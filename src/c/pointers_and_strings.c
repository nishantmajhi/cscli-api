/* Unit 9: Pointers
 * Pointers and character strings - a char pointer walking through a
 * string until the null terminator. */
#include <stdio.h>

int main(void) {
    char *str = "Hello, Pointers!";
    char *p = str;

    while (*p != '\0') {
        putchar(*p);
        p++;
    }
    putchar('\n');

    return 0;
}
